package util

import (
	"bytes"
	"context"
	"fmt"
	"io"
	"net/http"
	"strconv"

	"github.com/charmbracelet/log"
)

func LogRequest(_ context.Context, req *http.Request) (*log.Logger, error) {
	l := log.With("request_method", req.Method)

	if req.RequestURI != "" {
		l = l.With("request_uri", req.RequestURI)
	} else if req.URL != nil {
		l = l.With("url", req.URL)
	}

	if req.Host != "" {
		l = l.With("request_host", req.Host)
	}
	if req.Proto != "" {
		l = l.With("request_proto", req.Proto)
	}
	
	reqBody, err := io.ReadAll(req.Body)
	if err != nil {
		return nil, fmt.Errorf("failed to read request body: %w", err)
	}
	req.Body = io.NopCloser(bytes.NewBuffer(reqBody))

	l = l.With("request_body", string(reqBody))

	for k, vv := range req.Header {
		for i, v := range vv {
			l = l.With("request_header["+k+"]["+strconv.Itoa(i)+"]", v)
		}
	}

	return l, nil
}

func LogResponse(_ context.Context, resp *http.Response) (*log.Logger, error) {
	l := log.With("response_status_code", resp.StatusCode)

	respBody, err := io.ReadAll(resp.Body)
	if err != nil {
		return nil, fmt.Errorf("failed to read response body: %w", err)
	}
	resp.Body = io.NopCloser(bytes.NewBuffer(respBody))

	l = l.With("response_body", string(respBody))

	for k, vv := range resp.Header {
		for i, v := range vv {
			l = l.With("response_header["+k+"]["+strconv.Itoa(i)+"]", v)
		}
	}

	return l, nil
}
