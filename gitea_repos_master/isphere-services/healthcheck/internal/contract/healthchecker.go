package contract

import (
	"context"
	"time"

	"i-sphere.ru/healthcheck/internal/storage"
)

type Healthchecker interface {
	Name() string
	InspectionInterval() time.Duration
	Destinations() []HealthcheckDestination
	Check(ctx context.Context, events *storage.Events) error
}

type HealthcheckDestination string

const (
	HealthcheckDestinationServer     HealthcheckDestination = "server"
	HealthcheckDestinationNodeWorker HealthcheckDestination = "node_worker"
)
