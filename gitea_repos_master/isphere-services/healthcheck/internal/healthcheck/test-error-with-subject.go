package healthcheck

import (
	"context"
	"fmt"
	"time"

	"github.com/google/uuid"
	"i-sphere.ru/healthcheck/internal/contract"
	"i-sphere.ru/healthcheck/internal/storage"
)

type TestErrorWithSubject struct {
}

func NewTestErrorWithSubject() *TestErrorWithSubject {
	return new(TestErrorWithSubject)
}

func (e *TestErrorWithSubject) Name() string {
	return "test-error-with-subject"
}

func (e *TestErrorWithSubject) InspectionInterval() time.Duration {
	return 10 * time.Minute
}

func (e *TestErrorWithSubject) Destinations() []contract.HealthcheckDestination {
	return []contract.HealthcheckDestination{
		contract.HealthcheckDestinationServer,
	}
}

func (e *TestErrorWithSubject) Check(_ context.Context, events *storage.Events) error {
	requestID := uuid.New()
	events.RequestID = &requestID
	event := storage.NewEvent("trigger-error", requestID.String())
	start := time.Now()
	event = event.WithDuration(time.Since(start))
	err := fmt.Errorf("test error with subject: %s", requestID.String())
	events.Append(event.WithError(err))
	return err
}
