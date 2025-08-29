package pkg

import (
	"bytes"
	"context"
	"fmt"
	"io"
	"net/http"
	"strconv"
	"strings"
	"time"

	"github.com/corpix/uarand"
	"github.com/google/uuid"
	"i-sphere.ru/yoomoney/internal"
)

type SessionManager struct {
	client *http.Client

	defaultDuration time.Duration
}

func NewSessionManager(client *http.Client) *SessionManager {
	return &SessionManager{
		client: client,

		defaultDuration: 5 * time.Second,
	}
}

func (s *SessionManager) NewSession(ctx context.Context) (*internal.Session, error) {
	ctx, cancel := context.WithTimeout(ctx, s.defaultDuration)
	defer cancel()

	req, err := s.createRequest(ctx, "https://yoomoney.ru/transfer")
	if err != nil {
		return nil, fmt.Errorf("failed to create request: %w", err)
	}

	resp, err := s.client.Do(req)
	if err != nil {
		return nil, fmt.Errorf("failed to make request: %w", err)
	}
	//goland:noinspection GoUnhandledErrorResult
	defer resp.Body.Close()

	usedProxyID, err := strconv.Atoi(resp.Header.Get("X-Sphere-Proxy-Spec-Id"))
	if err != nil {
		return nil, fmt.Errorf("failed to parse X-Sphere-Proxy-Spec-Id: %w", err)
	}

	respBodyBytes, err := io.ReadAll(resp.Body)
	if err != nil {
		return nil, fmt.Errorf("failed to read response body: %w", err)
	}

	secretKey := s.extractSecretKey(respBodyBytes)
	if secretKey == "" {
		return nil, fmt.Errorf("failed to find secret key")
	}

	return &internal.Session{
		ID:      uuid.New(),
		ProxyID: usedProxyID,
		SecretKey: internal.SecretKey{
			UserAgent: req.Header.Get("User-Agent"),
			SecretKey: secretKey,
		},
	}, nil
}

func (s *SessionManager) createRequest(ctx context.Context, url string) (*http.Request, error) {
	req, err := http.NewRequestWithContext(ctx, http.MethodGet, url, http.NoBody)
	if err != nil {
		return nil, fmt.Errorf("failed to create request: %w", err)
	}

	userAgent := uarand.GetRandom()
	req.Header.Set("User-Agent", userAgent)
	req.Header.Set("Accept", "text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9")
	req.Header.Set("Accept-Language", "ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7")
	req.Header.Set("Cache-Control", "max-age=0")
	req.Header.Set("Connection", "keep-alive")
	req.Header.Set("Upgrade-Insecure-Requests", "1")
	req.Header.Set("Sec-Fetch-Dest", "document")
	req.Header.Set("Sec-Fetch-Mode", "navigate")
	req.Header.Set("Sec-Fetch-Site", "same-origin")
	req.Header.Set("Sec-Fetch-User", "?1")
	req.Header.Set("Sec-GPC", "1")
	req.Header.Set("Pragma", "no-cache")
	req.Header.Set("Referer", "https://yoomoney.ru/transfer")
	req.Header.Set("X-Sphere-Proxy-Spec-Country-Code", "ru")
	req.Header.Set("X-Sphere-Proxy-Spec-Group-Id", "5")

	return req, nil
}

func (s *SessionManager) extractSecretKey(bodyBytes []byte) string {
	pos := bytes.Index(bodyBytes, []byte(`"secretKey"`))
	if pos == -1 {
		return ""
	}

	var foundColon bool
	var firstQuoteFound bool
	var secretKeyBuilder strings.Builder
	for i := pos; i < len(bodyBytes); i++ {
		if !foundColon {
			if bodyBytes[i] == ':' {
				foundColon = true
			}
			continue
		}
		if !firstQuoteFound {
			if bodyBytes[i] == '"' {
				firstQuoteFound = true
			}
			continue
		}
		if bodyBytes[i] == '"' {
			break
		}
		secretKeyBuilder.WriteByte(bodyBytes[i])
	}

	return secretKeyBuilder.String()
}
