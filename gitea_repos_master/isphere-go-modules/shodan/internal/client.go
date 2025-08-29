package internal

import (
	"context"
	"encoding/json"
	"fmt"
	"io"
	"net/http"
	"net/url"
	"time"

	"github.com/sirupsen/logrus"
)

type Client struct {
	config     *Config
	httpClient *http.Client
}

func NewClient(config *Config) *Client {
	return &Client{
		config:     config,
		httpClient: http.DefaultClient,
	}
}

func (t *Client) GET(ctx context.Context, path string, responseObject any) error {
	reqURL, err := url.Parse(t.config.Host)
	if err != nil {
		return fmt.Errorf("failed to parse URL: %w", err)
	}

	reqURL.Path = path
	reqURL.RawQuery = url.Values{"key": []string{t.config.Token}}.Encode()

	req, err := http.NewRequestWithContext(ctx, http.MethodGet, reqURL.String(), http.NoBody)
	if err != nil {
		return fmt.Errorf("failed to make HTTP request: %w", err)
	}

	logrus.WithFields(logrus.Fields{
		"request_body":    nil,
		"request_headers": req.Header,
		"request_id":      ctx.Value("x-request-id"),
		"request_method":  req.Method,
		"request_proto":   req.Proto,
		"request_time":    time.Now().Format(time.RFC3339),
		"request_uri":     req.RequestURI,
	}).Debug("provider request")

	res, err := t.httpClient.Do(req)
	if err != nil {
		return fmt.Errorf("request error: %w", err)
	}

	content, err := io.ReadAll(res.Body)
	if err != nil {
		return fmt.Errorf("failed to read response body: %w", err)
	}

	//goland:noinspection GoUnhandledErrorResult
	defer res.Body.Close()

	logrus.WithFields(logrus.Fields{
		"request_id":       ctx.Value("x-request-id"),
		"response_body":    string(content),
		"response_headers": res.Header,
		"response_status":  res.StatusCode,
		"response_time":    time.Now().Format(time.RFC3339),
	}).Debug("provider response")

	if err = json.Unmarshal(content, &responseObject); err != nil {
		return fmt.Errorf("failed to unserialize CensysResponse to target object: %w", err)
	}

	return nil
}
