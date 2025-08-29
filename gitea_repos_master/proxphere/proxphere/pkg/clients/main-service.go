package clients

import (
	"context"
	"fmt"
	"io"
	"net/url"
	"os"
	"path/filepath"
	"strings"

	http "github.com/Danny-Dasilva/fhttp"
	"github.com/google/uuid"
	"go.i-sphere.ru/proxy/pkg/contracts"
)

type MainService struct {
	baseURL *url.URL
}

func NewMainService() (*MainService, error) {
	baseURL, err := url.Parse(os.Getenv("MAIN_SERVICE_BASE_URL"))
	if err != nil {
		return nil, fmt.Errorf("failed to parse base URL: %w", err)
	}

	baseURL.User = url.UserPassword(os.Getenv("MAIN_SERVICE_HTTP_BASIC_USERNAME"), os.Getenv("MAIN_SERVICE_HTTP_BASIC_PASSWORD"))

	return &MainService{
		baseURL: baseURL,
	}, nil
}

func (s *MainService) NewRequest(ctx context.Context, method string, path string, body io.Reader) (*http.Request, error) {
	path, params, _ := strings.Cut(path, "?")
	reqURL, err := url.ParseRequestURI(path)
	if err != nil {
		return nil, fmt.Errorf("failed to parse request URL: %w", err)
	}

	reqURL.Path = filepath.Join(s.baseURL.Path, path)
	reqURL.Scheme = s.baseURL.Scheme
	reqURL.Host = s.baseURL.Host
	reqURL.User = s.baseURL.User
	if params != "" {
		reqURL.RawQuery = params
	}

	req, err := http.NewRequestWithContext(ctx, method, reqURL.String(), body)
	if err != nil {
		return nil, fmt.Errorf("failed to create request: %w", err)
	}
	req.Header.Add(string(contracts.XRequestID), uuid.NewString())

	return req, nil
}
