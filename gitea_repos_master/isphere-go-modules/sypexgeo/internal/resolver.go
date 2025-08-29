package internal

import (
	"encoding/json"
	"errors"
	"fmt"
	"io"
	"net"
	"net/http"
	"net/url"
	"time"

	"git.i-sphere.ru/isphere-go-modules/sypexgeo/internal/model"
	"github.com/graphql-go/graphql"
	"github.com/sirupsen/logrus"
)

func Resolver(p graphql.ResolveParams) (any, error) {
	ipStr, ok := p.Args["ip"].(string)
	if !ok {
		return nil, errors.New("failed to resolve ip")
	}

	ip := net.ParseIP(ipStr)
	if ip == nil {
		return nil, errors.New("failed to parse ip")
	}

	reqURL := &url.URL{
		Scheme: "https", Host: "api.sypexgeo.net", Path: "/json/" + ip.String(),
	}

	req, err := http.NewRequest(http.MethodGet, reqURL.String(), http.NoBody)
	if err != nil {
		return nil, fmt.Errorf("failed to make http request: %v", err)
	}

	logrus.WithFields(logrus.Fields{
		"request_body":    nil,
		"request_headers": req.Header,
		"request_id":      p.Context.Value("x-request-id"),
		"request_method":  req.Method,
		"request_proto":   req.Proto,
		"request_time":    time.Now().Format(time.RFC3339),
		"request_uri":     req.RequestURI,
	}).Debug("provider request")

	res, err := http.DefaultClient.Do(req)
	if err != nil {
		return nil, fmt.Errorf("failed to send http request: %v", err)
	}

	content, err := io.ReadAll(res.Body)
	if err != nil {
		return nil, fmt.Errorf("failed to read response body: %w", err)
	}

	//goland:noinspection GoUnhandledErrorResult
	defer res.Body.Close()

	logrus.WithFields(logrus.Fields{
		"request_id":       p.Context.Value("x-request-id"),
		"response_body":    string(content),
		"response_headers": res.Header,
		"response_status":  res.StatusCode,
		"response_time":    time.Now().Format(time.RFC3339),
	}).Debug("provider response")

	var response model.Response
	if err = json.Unmarshal(content, &response); err != nil {
		return nil, fmt.Errorf("failed to unmarshal response: %v", err)
	}

	if response.Error != "" {
		return nil, fmt.Errorf("gateway response error: %v", response.Error)
	}

	return []*model.Response{&response}, nil
}
