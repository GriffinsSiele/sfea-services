package cli

import (
	"context"

	"i-sphere.ru/healthcheck/internal/contract"
	"i-sphere.ru/healthcheck/internal/env"
	"i-sphere.ru/healthcheck/internal/storage"
)

type NodeWorker struct {
	worker
	healthchecks []contract.Healthchecker
	info         *env.ClusterInfo
	memory       *storage.Memory
}

func NewNodeWorker(healthchecks []contract.Healthchecker, info *env.ClusterInfo, memory *storage.Memory) *NodeWorker {
	return &NodeWorker{
		healthchecks: healthchecks,
		info:         info,
		memory:       memory,
	}
}

func (w *NodeWorker) Run(ctx context.Context) error {
	return w.runDestination(ctx, w.memory, w.info, w.healthchecks, contract.HealthcheckDestinationNodeWorker)
}
