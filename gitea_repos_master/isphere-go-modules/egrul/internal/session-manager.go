package internal

import (
	"context"
	"net/http"
	"strconv"
	"time"

	"github.com/corpix/uarand"
	"github.com/google/uuid"
	"github.com/pkg/errors"
)

type SessionManager struct {
	client *http.Client

	defaultDuration time.Duration
}

func NewSessionManager(client *http.Client) *SessionManager {
	return &SessionManager{
		client: client,

		defaultDuration: 10 * time.Second,
	}
}

func (s *SessionManager) NewSession(ctx context.Context) (*Session, error) {
	ctx, cancel := context.WithTimeout(ctx, s.defaultDuration)
	defer cancel()

	req, err := s.newRequest(ctx, "https://egrul.nalog.ru/index.html")
	if err != nil {
		return nil, errors.Wrap(err, "failed to create request")
	}

	resp, err := s.client.Do(req)
	if err != nil {
		return nil, errors.Wrap(err, "failed to do request")
	}
	//goland:noinspection GoUnhandledErrorResult
	defer resp.Body.Close()

	if resp.StatusCode != http.StatusOK {
		return nil, errors.Wrapf(err, "unexpected status code: %d", resp.StatusCode)
	}

	session := Session{
		ID:        uuid.New(),
		UserAgent: req.Header.Get("User-Agent"),
		Cookies:   resp.Cookies(),
	}

	var foundExpectedCookie bool
	for _, c := range session.Cookies {
		if c.Name == "JSESSIONID" {
			foundExpectedCookie = true
			break
		}
	}

	if !foundExpectedCookie {
		return nil, errors.New("failed to find JSESSIONID cookie")
	}

	if session.ProxyID, err = strconv.Atoi(resp.Header.Get("X-Sphere-Proxy-Spec-Id")); err != nil {
		return nil, errors.Wrapf(err, "failed to parse X-Sphere-Proxy-Spec-Id: %s", resp.Header.Get("X-Sphere-Proxy-Spec-Id"))
	}

	return &session, nil
}

func (s *SessionManager) newRequest(ctx context.Context, url string) (*http.Request, error) {
	req, err := http.NewRequestWithContext(ctx, http.MethodGet, url, http.NoBody)
	if err != nil {
		return nil, errors.Wrap(err, "failed to create request")
	}

	req.Header.Set("Accept", "text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8")
	req.Header.Set("Accept-Encoding", "gzip, deflate, br")
	req.Header.Set("Accept-Language", "ru")
	req.Header.Set("Connection", "keep-alive")
	req.Header.Set("Sec-Fetch-Dest", "document")
	req.Header.Set("Sec-Fetch-Mode", "navigate")
	req.Header.Set("Sec-Fetch-Site", "none")
	req.Header.Set("User-Agent", uarand.GetRandom())

	req.Header.Set("X-Sphere-Proxy-Spec-Country-Code", "ru")
	req.Header.Set("X-Sphere-Proxy-Spec-Group-Id", "5")

	return req, nil
}
