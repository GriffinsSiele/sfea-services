package client

import (
	"context"
	"net/http"
	"net/url"
	"testing"

	"github.com/stretchr/testify/assert"
)

func TestHTTPWithoutOptions(t *testing.T) {
	t.Parallel()

	var (
		httpClient      = NewHTTP(&MockHTTPClient{})
		httpURL, _      = url.Parse("https://example.com")
		httpResponse, _ = httpClient.GET(context.Background(), httpURL)
		httpRequest     = httpResponse.Request
	)

	assert.Equal(t, 0, len(httpRequest.Header))
	assert.Equal(t, 0, len(httpRequest.URL.Query()))
}

func TestHTTPWithQueryParamOption(t *testing.T) {
	t.Parallel()

	var (
		httpClient      = NewHTTP(&MockHTTPClient{})
		httpURL, _      = url.Parse("https://example.com")
		httpResponse, _ = httpClient.GET(context.Background(), httpURL, QueryParam("some", "value"))
		httpRequest     = httpResponse.Request
	)

	assert.Equal(t, 0, len(httpRequest.Header))
	assert.Equal(t, 1, len(httpRequest.URL.Query()))
	assert.True(t, httpRequest.URL.Query().Has("some"))
}

func TestHTTPWithRandomUserAgentOption(t *testing.T) {
	t.Parallel()

	var (
		httpClient      = NewHTTP(&MockHTTPClient{})
		httpURL, _      = url.Parse("https://example.com")
		httpResponse, _ = httpClient.GET(context.Background(), httpURL, RandomUserAgent())
		httpRequest     = httpResponse.Request
	)

	assert.Equal(t, 1, len(httpRequest.Header))
	assert.NotEmpty(t, httpRequest.Header.Get("User-Agent"))
	assert.Equal(t, 0, len(httpRequest.URL.Query()))
}

// ---

type MockHTTPClient struct{}

func (t *MockHTTPClient) Do(req *http.Request) (*http.Response, error) {
	return &http.Response{
		StatusCode: http.StatusOK,
		Request:    req,
	}, nil
}
