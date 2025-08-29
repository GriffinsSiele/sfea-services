package healthcheck

import (
	"context"
	"errors"
	"time"

	"github.com/google/uuid"
	"i-sphere.ru/healthcheck/internal/contract"
	"i-sphere.ru/healthcheck/internal/storage"
)

type TestError struct {
}

func NewTestError() *TestError {
	return new(TestError)
}

func (e *TestError) Name() string {
	return "test-error"
}

func (e *TestError) InspectionInterval() time.Duration {
	return 10 * time.Minute
}

func (e *TestError) Destinations() []contract.HealthcheckDestination {
	return []contract.HealthcheckDestination{
		contract.HealthcheckDestinationServer,
	}
}

func (e *TestError) Check(_ context.Context, events *storage.Events) error {
	requestID := uuid.New()
	events.RequestID = &requestID
	event := storage.NewEvent("trigger-error", "")
	start := time.Now()
	event = event.WithDuration(time.Since(start))
	err := errors.New("test error")
	events.Append(event.WithError(err))
	return err
}
