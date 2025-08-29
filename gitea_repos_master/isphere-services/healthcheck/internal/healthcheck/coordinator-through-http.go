package healthcheck

import (
	"bytes"
	"context"
	"fmt"
	"io"
	"net/http"
	"time"

	"github.com/google/uuid"
	"i-sphere.ru/healthcheck/internal/contract"
	"i-sphere.ru/healthcheck/internal/env"
	"i-sphere.ru/healthcheck/internal/model/geoip"
	"i-sphere.ru/healthcheck/internal/storage"
	"i-sphere.ru/healthcheck/internal/util"
)

type CoordinatorThroughHTTP struct {
	params *env.Params
}

func NewCoordinatorThroughHTTP(params *env.Params) *CoordinatorThroughHTTP {
	return &CoordinatorThroughHTTP{
		params: params,
	}
}

func (c *CoordinatorThroughHTTP) Name() string {
	return "coordinator-through-http"
}

func (c *CoordinatorThroughHTTP) InspectionInterval() time.Duration {
	return 1 * time.Minute
}

func (c *CoordinatorThroughHTTP) Destinations() []contract.HealthcheckDestination {
	return []contract.HealthcheckDestination{
		contract.HealthcheckDestinationServer,
	}
}

func (c *CoordinatorThroughHTTP) Check(ctx context.Context, events *storage.Events) error {
	requestID := uuid.New()
	events.RequestID = &requestID

	requestBytes := geoip.NewRequest("185.158.155.34", requestID.String()).Bytes()
	requestURL := fmt.Sprintf("%s/api/v1/check-types/geoip", c.params.CoordinatorURL)
	req, err := http.NewRequestWithContext(ctx, http.MethodPost, requestURL, bytes.NewReader(requestBytes))
	if err != nil {
		return fmt.Errorf("failed to create request: %w", err)
	}
	req.Header.Set("Content-Type", "application/json")
	req.Header.Set("Accept", "application/json")
	req.Header.Set("X-Request-ID", requestID.String())

	event := storage.NewEvent("http-request", c.params.CoordinatorURL).
		With("request", map[string]any{
			"method":  req.Method,
			"url":     req.URL.String(),
			"headers": string(util.MustMarshal(util.NormalizeHeaders(req.Header))),
			"body":    string(requestBytes),
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
		return fmt.Errorf("failed to read response: %w", err)
	}
	event = event.With("response", map[string]any{
		"status_code": resp.StatusCode,
		"headers":     string(util.MustMarshal(util.NormalizeHeaders(resp.Header))),
		"body":        string(respBytes),
	})

	response := new(geoip.Response)
	util.MustUnmarshal(respBytes, response)

	if err = response.Validate(); err != nil {
		events.Append(event.WithError(err))
		return fmt.Errorf("invalid response: %w", err)
	}

	events.Append(event)
	return nil
}
