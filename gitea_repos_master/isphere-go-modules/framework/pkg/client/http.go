package client

import (
	"context"
	"fmt"
	"net/http"
	"net/url"

	"git.i-sphere.ru/isphere-go-modules/framework/pkg/contract"
	"github.com/corpix/uarand"
)

type HTTP struct {
	httpClient contract.HTTPClient
}

func NewHTTP(httpClient contract.HTTPClient) *HTTP {
	return &HTTP{
		httpClient: httpClient,
	}
}

func (t *HTTP) GET(ctx context.Context, uri *url.URL, options ...Option) (*http.Response, error) {
	headers, queryParams := t.prepare(options)

	uri.RawQuery = queryParams.Encode()

	httpRequest, err := http.NewRequestWithContext(ctx, http.MethodGet, uri.String(), http.NoBody)
	if err != nil {
		return nil, fmt.Errorf("failed to create HTTP request data: %w", err)
	}

	for k, values := range headers {
		for _, value := range values {
			httpRequest.Header.Add(k, value)
		}
	}

	httpResponse, err := t.httpClient.Do(httpRequest)
	if err != nil {
		return nil, fmt.Errorf("failed to execute HTTP request: %w", err)
	}

	return httpResponse, nil
}

func (t *HTTP) prepare(options []Option) (headers, queryParams url.Values) {
	headers = make(url.Values)
	queryParams = make(url.Values)

	for _, option := range options {
		switch v := option.(type) {
		case *QueryParamOption:
			for _, value := range v.values {
				queryParams.Add(v.key, value)
			}

		case *randomUserAgentOption:
			headers.Add("User-Agent", uarand.GetRandom())
		}
	}

	return headers, queryParams
}

type Headers map[string]string

type Option interface{}

type QueryParamOption struct {
	Option
	key    string
	values []string
}

func QueryParam(key string, values ...string) Option {
	return &QueryParamOption{
		key:    key,
		values: values,
	}
}

type randomUserAgentOption struct{}

func RandomUserAgent() Option {
	return &randomUserAgentOption{}
}
