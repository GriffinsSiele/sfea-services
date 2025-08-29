package internal

import (
	"bytes"
	"context"
	"encoding/base64"
	"encoding/json"
	"fmt"
	"io"
	"net/http"
	"net/url"
	"strconv"
	"strings"
	"sync"
	"time"

	"github.com/pkg/errors"
	"github.com/sirupsen/logrus"
)

type HTTPController struct {
	AbstractController

	client         *http.Client
	sessionWatcher *SessionWatcher
	pdfParser      *PDFParser
}

func NewHTTPController(client *http.Client, sessionWatcher *SessionWatcher, pdfParser *PDFParser) *HTTPController {
	return &HTTPController{
		client:         client,
		sessionWatcher: sessionWatcher,
		pdfParser:      pdfParser,
	}
}

func (h *HTTPController) ServeHTTP(w http.ResponseWriter, r *http.Request) {
	var input Input
	if err := json.NewDecoder(r.Body).Decode(&input); err != nil {
		h.error(w, err, http.StatusUnprocessableEntity)
		return
	}
	if input.INN == "" {
		h.error(w, errors.New("empty inn"), http.StatusUnprocessableEntity)
		return
	}

	formData := url.Values{
		"vyp3CaptchaToken":          {""},
		"page":                      {""},
		"query":                     {input.INN},
		"region":                    {""},
		"PreventChromeAutocomplete": {""},
	}

	var lastErr error

	for i := 0; i < 5; i++ {
		log := logrus.WithContext(r.Context()).WithFields(logrus.Fields{"i": i})
		log.Debug("waiting for session")

		session, err := h.waitForSession(r.Context())
		if err != nil || session == nil {
			lastErr = err
			continue
		} else {
			log = log.WithField("session_id", session.ID)
			log.Debug("got session")
		}

		log.Debug("performing egrul response")
		egrulResponse, err := h.performEgrulResponse(r.Context(), session, formData)
		if err != nil {
			lastErr = err
			h.sessionWatcher.Delete(r.Context(), session)
			continue
		} else {
			log.WithField("egrul_response", egrulResponse).Debug("got egrul response")
		}

		log.Debug("performing search result")
		searchResults, err := h.performSearchResults(r.Context(), session, egrulResponse)
		if err != nil {
			lastErr = err
			h.sessionWatcher.Delete(r.Context(), session)
			continue
		} else {
			log.WithField("search_results", searchResults).Debug("got search result")
		}

		if len(searchResults) == 0 {
			h.sessionWatcher.Free(session)
			h.success(w, nil)
			return
		}

		var wg sync.WaitGroup
		for _, sr := range searchResults {
			wg.Add(1)
			go func(sr *SearchResult, session *Session) {
				defer wg.Done()

				log.WithField("search_result", sr).Debug("download search result file")
				if sr.PDFData, err = h.performPDFFile(r.Context(), session, sr); err != nil {
					logrus.WithContext(r.Context()).WithError(err).Error("failed to perform pdf file")
				}
			}(sr, session)
		}
		wg.Wait()

		results := make([]*SearchResult, len(searchResults))
		for j, sr := range searchResults {
			results[j] = sr
		}

		h.sessionWatcher.Free(session)
		h.success(w, results)
		return
	}

	h.error(w, lastErr, http.StatusInternalServerError)
}

func (h *HTTPController) performEgrulResponse(ctx context.Context, session *Session, formData url.Values) (*EgrulResponse, error) {
	req, err := http.NewRequestWithContext(ctx, http.MethodPost, "https://egrul.nalog.ru/", strings.NewReader(formData.Encode()))
	if err != nil {
		return nil, errors.Wrap(err, "failed to create request")
	}

	req.Header.Set("Content-Type", "application/x-www-form-urlencoded; charset=UTF-8")
	req.Header.Set("Accept", "application/json, text/javascript, */*; q=0.01")
	req.Header.Set("Sec-Fetch-Site", "same-origin")
	req.Header.Set("Accept-Language", "ru")
	req.Header.Set("Accept-Encoding", "gzip, deflate, br")
	req.Header.Set("Sec-Fetch-Mode", "cors")
	req.Header.Set("Origin", "https://egrul.nalog.ru")
	req.Header.Set("User-Agent", session.UserAgent)
	req.Header.Set("Referer", "https://egrul.nalog.ru/index.html")
	req.Header.Set("Connection", "keep-alive")
	req.Header.Set("Sec-Fetch-Dest", "empty")
	for _, c := range session.Cookies {
		req.AddCookie(c)
	}
	req.Header.Set("X-Requested-With", "XMLHttpRequest")

	req.Header.Set("X-Sphere-Proxy-Spec-Id", strconv.Itoa(session.ProxyID))

	resp, err := h.client.Do(req)
	if err != nil {
		return nil, errors.Wrap(err, "failed to perform request")
	}
	//goland:noinspection GoUnhandledErrorResult
	defer resp.Body.Close()

	if resp.StatusCode != http.StatusOK {
		return nil, fmt.Errorf("unexpected status code: %d", resp.StatusCode)
	}

	var response EgrulResponse
	if err = json.NewDecoder(resp.Body).Decode(&response); err != nil {
		return nil, errors.Wrap(err, "failed to decode response")
	}

	if response.CaptchaRequired {
		return nil, errors.New("captcha required")
	}

	return &response, nil
}

func (h *HTTPController) performSearchResults(ctx context.Context, session *Session, egrulResponse *EgrulResponse) ([]*SearchResult, error) {
	timeStr := strconv.FormatInt(time.Now().UnixMilli(), 10)
	reqURLStr := fmt.Sprintf("https://egrul.nalog.ru/search-result/%s?%s", egrulResponse.Token, url.Values{
		"r": {timeStr},
		"_": {timeStr},
	}.Encode())
	req, err := http.NewRequestWithContext(ctx, http.MethodGet, reqURLStr, http.NoBody)
	if err != nil {
		return nil, errors.Wrap(err, "failed to create request")
	}

	h.withBaseHeaders(req, session)

	resp, err := h.client.Do(req)
	if err != nil {
		return nil, errors.Wrap(err, "failed to perform request")
	}
	//goland:noinspection GoUnhandledErrorResult
	defer resp.Body.Close()

	if resp.StatusCode != http.StatusOK {
		return nil, fmt.Errorf("unexpected status code: %d", resp.StatusCode)
	}

	var response struct {
		Rows []*SearchResult `json:"rows"`
	}
	if err = json.NewDecoder(resp.Body).Decode(&response); err != nil {
		return nil, errors.Wrap(err, "failed to decode response")
	}

	return response.Rows, nil
}

func (h *HTTPController) performPDFFile(ctx context.Context, session *Session, sr *SearchResult) (any, error) {
	logrus.WithContext(ctx).Debug("start generating pdf file")
	if _, err := h.performPDFFileStart(ctx, session, sr); err != nil {
		return nil, errors.Wrap(err, "failed to start generating pdf file")
	}

	ctx, cancel := context.WithTimeout(ctx, 30*time.Second)
	defer cancel()

	logrus.WithContext(ctx).Debug("initialize pdf status")
	status, err := h.performPDFFileStatus(ctx, session, sr)
	if err != nil {
		return nil, errors.Wrap(err, "failed to initialize pdf file status")
	}

	logrus.WithContext(ctx).WithField("status", status).Debug("initial status is")
	if status == "wait" {
	l:
		for {
			select {
			case <-ctx.Done():
				return nil, ctx.Err()
			case <-time.After(1 * time.Second):
				logrus.WithContext(ctx).Debug("updating pdf status")
				if status, err = h.performPDFFileStatus(ctx, session, sr); err != nil {
					return nil, errors.Wrap(err, "failed to update pdf file status")
				}
				logrus.WithContext(ctx).WithField("status", status).Debug("updated status")
				if status == "ready" {
					break l
				}
			}
		}
	}

	if status != "ready" {
		return nil, fmt.Errorf("unexpected final file status: %s", status)
	}

	logrus.WithContext(ctx).Debug("downloading generated pdf file")
	pdfBytesInBase64, err := h.performPDF(ctx, session, sr)
	if err != nil {
		return nil, errors.Wrap(err, "failed to download pdf file")
	} else {
		logrus.WithContext(ctx).Debug("pdf file downloaded")
	}

	logrus.WithContext(ctx).Debug("decoding pdf file")
	pdfBytes, err := base64.StdEncoding.DecodeString(string(pdfBytesInBase64))
	if err != nil {
		return nil, errors.Wrap(err, "failed to decode pdf file")
	} else {
		logrus.WithContext(ctx).Debug("pdf file decoded")
	}

	logrus.WithContext(ctx).Debug("parsing pdf file")
	pdfData, err := h.pdfParser.Parse(bytes.NewReader(pdfBytes), int64(len(pdfBytes)))
	if err != nil {
		return nil, errors.Wrap(err, "failed to parse pdf file")
	} else {
		logrus.WithContext(ctx).Debug("pdf file parsed")
	}

	return pdfData, nil
}

func (h *HTTPController) performPDFFileStart(ctx context.Context, session *Session, sr *SearchResult) (any, error) {
	timeStr := strconv.FormatInt(time.Now().UnixMilli(), 10)
	reqURLStr := fmt.Sprintf("https://egrul.nalog.ru/vyp-request/%s?%s", sr.Token, url.Values{
		"r": {""}, // yes, here does not need
		"_": {timeStr},
	}.Encode())
	req, err := http.NewRequestWithContext(ctx, http.MethodGet, reqURLStr, http.NoBody)
	if err != nil {
		return nil, errors.Wrap(err, "failed to create request")
	}

	h.withBaseHeaders(req, session)

	resp, err := h.client.Do(req)
	if err != nil {
		return nil, errors.Wrap(err, "failed to perform request")
	}
	//goland:noinspection GoUnhandledErrorResult
	defer resp.Body.Close()

	var response EgrulResponse
	if err = json.NewDecoder(resp.Body).Decode(&response); err != nil {
		return nil, errors.Wrap(err, "failed to decode response")
	}

	if response.CaptchaRequired {
		return nil, errors.New("captcha required")
	}

	return nil, nil
}

func (h *HTTPController) performPDFFileStatus(ctx context.Context, session *Session, sr *SearchResult) (string, error) {
	timeStr := strconv.FormatInt(time.Now().UnixMilli(), 10)
	reqURLStr := fmt.Sprintf("https://egrul.nalog.ru/vyp-status/%s?%s", sr.Token, url.Values{
		"r": {timeStr},
		"_": {timeStr},
	}.Encode())
	req, err := http.NewRequestWithContext(ctx, http.MethodGet, reqURLStr, http.NoBody)
	if err != nil {
		return "", errors.Wrap(err, "failed to create request")
	}

	h.withBaseHeaders(req, session)

	resp, err := h.client.Do(req)
	if err != nil {
		return "", errors.Wrap(err, "failed to perform request")
	}
	//goland:noinspection GoUnhandledErrorResult
	defer resp.Body.Close()

	if resp.StatusCode != http.StatusOK {
		return "", fmt.Errorf("unexpected status code: %d", resp.StatusCode)
	}

	var response struct {
		Status string `json:"status"`
	}
	if err = json.NewDecoder(resp.Body).Decode(&response); err != nil {
		return "", errors.Wrap(err, "failed to decode response")
	}

	return response.Status, nil
}

func (h *HTTPController) performPDF(ctx context.Context, session *Session, sr *SearchResult) ([]byte, error) {
	timeStr := strconv.FormatInt(time.Now().UnixMilli(), 10)
	reqURLStr := fmt.Sprintf("https://egrul.nalog.ru/vyp-download/%s?%s", sr.Token, url.Values{
		"r": {timeStr},
		"_": {timeStr},
	}.Encode())
	req, err := http.NewRequestWithContext(ctx, http.MethodGet, reqURLStr, http.NoBody)
	if err != nil {
		return nil, errors.Wrap(err, "failed to create request")
	}

	h.withBaseHeaders(req, session)

	resp, err := h.client.Do(req)
	if err != nil {
		return nil, errors.Wrap(err, "failed to perform request")
	}
	//goland:noinspection GoUnhandledErrorResult
	defer resp.Body.Close()

	if resp.StatusCode != http.StatusOK {
		return nil, fmt.Errorf("unexpected status code: %d", resp.StatusCode)
	}

	pdfBytes, err := io.ReadAll(resp.Body)
	if err != nil {
		return nil, errors.Wrap(err, "failed to read pdf file contents")
	}

	return pdfBytes, nil
}

func (h *HTTPController) waitForSession(ctx context.Context) (*Session, error) {
	ctx, cancel := context.WithTimeout(ctx, 10*time.Second)
	defer cancel()

	ch := make(chan *Session)
	go h.sessionWatcher.One(ctx, ch)

	select {
	case s := <-ch:
		if s == nil {
			return nil, errors.New("no session found")
		}
		return s, nil
	case <-ctx.Done():
		return nil, ctx.Err()
	}
}

func (h *HTTPController) withBaseHeaders(req *http.Request, session *Session) {
	req.Header.Set("Accept", "*/*")
	req.Header.Set("Accept-Encoding", "gzip, deflate, br")
	req.Header.Set("Accept-Language", "ru")
	req.Header.Set("Connection", "keep-alive")
	for _, c := range session.Cookies {
		req.AddCookie(c)
	}
	req.Header.Set("Referer", "https://egrul.nalog.ru/index.html")
	req.Header.Set("Sec-Fetch-Dest", "empty")
	req.Header.Set("Sec-Fetch-Mode", "cors")
	req.Header.Set("Sec-Fetch-Site", "same-origin")
	req.Header.Set("User-Agent", session.UserAgent)
	req.Header.Set("X-Requested-With", "XMLHttpRequest")

	req.Header.Set("X-Sphere-Proxy-Spec-Id", strconv.Itoa(session.ProxyID))
}

func (h *HTTPController) success(w http.ResponseWriter, data []*SearchResult) {
	w.Header().Set("Content-Type", "application/json")
	if len(data) > 0 {
		w.WriteHeader(http.StatusOK)
	} else {
		w.WriteHeader(http.StatusNoContent)
	}
	//goland:noinspection GoUnhandledErrorResult
	json.NewEncoder(w).Encode(map[string]any{"data": data})
}
