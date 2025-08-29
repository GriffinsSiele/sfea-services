package clients

import (
	"bytes"
	"context"
	"fmt"
	"net/http"
	"net/http/httputil"
	"os"

	"github.com/charmbracelet/log"
)

type Debezium struct {
}

func NewDebezium() *Debezium {
	return new(Debezium)
}

func (d *Debezium) CreateSource(ctx context.Context, body []byte) error {
	bodyReader := bytes.NewReader(body)
	req, err := http.NewRequestWithContext(ctx, http.MethodPost, os.Getenv("SOURCE_CONNECTOR_URL")+"/connectors", bodyReader)
	if err != nil {
		return fmt.Errorf("failed to create request: %w", err)
	}

	req.Header.Set("Content-Type", "application/json")
	req.Header.Set("Accept", "application/json")

	reqBytes, err := httputil.DumpRequestOut(req, true)
	if err != nil {
		return fmt.Errorf("failed to dump request: %w", err)
	}
	log.With("body", string(reqBytes)).Info("sending request")

	resp, err := http.DefaultClient.Do(req)
	if err != nil {
		return fmt.Errorf("failed to send request: %w", err)
	}
	//goland:noinspection GoUnhandledErrorResult
	defer resp.Body.Close()

	respBytes, err := httputil.DumpResponse(resp, true)
	if err != nil {
		return fmt.Errorf("failed to dump response: %w", err)
	}
	l := log.With("body", string(respBytes))
	if resp.StatusCode >= 400 {
		l.Error("request failed")
		return fmt.Errorf("request failed with status %d", resp.StatusCode)
	}
	l.Info("received response")

	return nil
}

func (d *Debezium) DeleteSourceByName(ctx context.Context, name string) error {
	req, err := http.NewRequestWithContext(ctx, http.MethodDelete, os.Getenv("SOURCE_CONNECTOR_URL")+"/connectors/"+name, http.NoBody)
	if err != nil {
		return fmt.Errorf("failed to create request: %w", err)
	}

	reqBytes, err := httputil.DumpRequestOut(req, true)
	if err != nil {
		return fmt.Errorf("failed to dump request: %w", err)
	}
	log.With("body", string(reqBytes)).Info("sending request")

	resp, err := http.DefaultClient.Do(req)
	if err != nil {
		return fmt.Errorf("failed to send request: %w", err)
	}
	//goland:noinspection GoUnhandledErrorResult
	defer resp.Body.Close()

	respBytes, err := httputil.DumpResponse(resp, true)
	if err != nil {
		return fmt.Errorf("failed to dump response: %w", err)
	}
	l := log.With("body", string(respBytes))
	if resp.StatusCode >= 400 {
		l.Error("request failed")
		return fmt.Errorf("request failed with status %d", resp.StatusCode)
	}
	l.Info("received response")

	return nil
}
