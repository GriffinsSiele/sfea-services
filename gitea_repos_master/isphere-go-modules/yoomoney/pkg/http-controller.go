package pkg

import (
	"bytes"
	"context"
	"encoding/json"
	"errors"
	"net/http"
	"os"
	"strconv"
	"strings"
	"time"

	"github.com/sirupsen/logrus"
	"i-sphere.ru/yoomoney/internal"
)

type HTTPController struct {
	client         *http.Client
	sessionWatcher *SessionWatcher
}

func NewHTTPController(client *http.Client, sessionWatcher *SessionWatcher) *HTTPController {
	return &HTTPController{
		client:         client,
		sessionWatcher: sessionWatcher,
	}
}

func (c *HTTPController) ServeHTTP(w http.ResponseWriter, r *http.Request) {
	var input *internal.Input
	if err := json.NewDecoder(r.Body).Decode(&input); err != nil {
		c.error(w, err, http.StatusUnprocessableEntity)
		return
	}

	var formData internal.YoomoneyRequest
	formData.WithCredentials = true
	formData.Params.Origin = internal.ParamsOriginWithdraw
	switch {
	case input.Email != "":
		formData.Params.Recipient.Email = input.Email
	case input.Phone != "":
		formData.Params.Recipient.Phone = strings.TrimPrefix(input.Phone, "+")
	default:
		c.error(w, errors.New("email or phone required"), http.StatusUnprocessableEntity)
		return
	}

	formDataBytes, err := json.Marshal(formData)
	if err != nil {
		c.error(w, err, http.StatusInternalServerError)
		return
	}

	for i := 0; i < 5; i++ {
		session, err := c.waitForSession(r.Context())
		if err != nil {
			c.error(w, err, http.StatusInternalServerError)
			return
		}

		req, err := http.NewRequestWithContext(r.Context(), http.MethodPost, os.Getenv("YOOMONEY_ENDPOINT"), bytes.NewReader(formDataBytes))
		if err != nil {
			c.sessionWatcher.Free(session)
			c.error(w, err, http.StatusInternalServerError)
			return
		}

		req.Header.Set("Content-Type", "application/json")
		req.Header.Set("Origin", "https://yoomoney.ru")
		req.Header.Set("Referer", "https://yoomoney.ru/transfer/a2w")
		req.Header.Set("User-Agent", session.SecretKey.UserAgent)
		req.Header.Set("X-Csrf-Token", session.SecretKey.SecretKey)
		req.Header.Set("X-Sphere-Proxy-Spec-Id", strconv.Itoa(session.ProxyID))

		resp, err := c.client.Do(req)
		if err != nil {
			c.sessionWatcher.Delete(r.Context(), session)
			logrus.WithContext(r.Context()).WithError(err).Warn("failed to perform request")
			continue
		}
		c.sessionWatcher.Free(session)
		if resp.StatusCode != http.StatusCreated {
			c.sessionWatcher.Delete(r.Context(), session)
			logrus.WithContext(r.Context()).WithField("status", resp.StatusCode).Warn("unexpected status code")
			continue
		}

		var response internal.Root
		if err = json.NewDecoder(resp.Body).Decode(&response); err != nil {
			c.error(w, err, http.StatusInternalServerError)
			return
		}

		if response.RecipientInfo.AccountInfo.Identification == "" {
			c.success(w, []any{})
		} else {
			c.success(w, []any{response.RecipientInfo})
		}
		return
	}

	c.error(w, errors.New("all attempts failed"), http.StatusBadGateway)
}

func (c *HTTPController) waitForSession(ctx context.Context) (*internal.Session, error) {
	ctx, cancel := context.WithTimeout(ctx, 10*time.Second)
	defer cancel()

	ch := make(chan *internal.Session)
	go c.sessionWatcher.One(ctx, ch)

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

func (c *HTTPController) error(w http.ResponseWriter, err error, code int) {
	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(code)
	//goland:noinspection GoUnhandledErrorResult
	json.NewEncoder(w).Encode(map[string]string{"error": err.Error()})
}

func (c *HTTPController) success(w http.ResponseWriter, data []any) {
	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(http.StatusOK)
	//goland:noinspection GoUnhandledErrorResult
	json.NewEncoder(w).Encode(map[string][]any{"data": data})
}
