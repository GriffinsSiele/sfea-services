package healthcheck

import (
	"bytes"
	"context"
	"fmt"
	"io"
	"net/http"
	"net/url"
	"time"

	"github.com/google/uuid"
	"i-sphere.ru/healthcheck/internal/contract"
	"i-sphere.ru/healthcheck/internal/env"
	"i-sphere.ru/healthcheck/internal/storage"
	"i-sphere.ru/healthcheck/internal/util"
)

type MainService struct {
	params *env.Params
}

func NewMainService(p *env.Params) *MainService {
	return &MainService{
		params: p,
	}
}

func (s *MainService) Name() string {
	return "main-service"
}

func (s *MainService) InspectionInterval() time.Duration {
	return 1 * time.Minute
}

func (s *MainService) Destinations() []contract.HealthcheckDestination {
	return []contract.HealthcheckDestination{
		contract.HealthcheckDestinationServer,
	}
}

func (s *MainService) Check(ctx context.Context, events *storage.Events) error {
	requestID := uuid.New()
	events.RequestID = &requestID

	requestURL, err := url.Parse(fmt.Sprintf("%s/echo.php", s.params.MainServiceHost))
	if err != nil {
		return fmt.Errorf("failed to parse url: %w", err)
	}

	req, err := http.NewRequestWithContext(ctx, http.MethodGet, requestURL.String(), http.NoBody)
	if err != nil {
		return fmt.Errorf("failed to create request: %w", err)
	}

	req.Header.Set("X-Request-ID", requestID.String())

	event := storage.NewEvent("http-request", s.params.MainServiceHost).
		With("request", map[string]any{
			"method":  req.Method,
			"url":     req.URL.String(),
			"headers": string(util.MustMarshal(util.NormalizeHeaders(req.Header))),
		})

	start := time.Now()
	resp, err := http.DefaultClient.Do(req)
	event = event.WithDuration(time.Since(start))
	if err != nil {
		events.Append(event.WithError(err))
		return fmt.Errorf("failed to send request: %w", err)
	}
	//goland:noinspection GoUnhandledErrorResult
	defer resp.Body.Close()

	respBytes, err := io.ReadAll(resp.Body)
	if err != nil {
		events.Append(event.WithError(err))
		return fmt.Errorf("failed to read response body: %w", err)
	}
	event = event.With("response", map[string]any{
		"status_code": resp.StatusCode,
		"headers":     string(util.MustMarshal(util.NormalizeHeaders(resp.Header))),
		"body":        string(respBytes),
	})

	if !bytes.Equal(respBytes, []byte("OK")) {
		err := fmt.Errorf("unexpected response body: %s", respBytes)
		events.Append(event.WithError(err))
		return err
	}

	events.Append(event)
	return nil
}
