package cli

import (
	"context"

	"i-sphere.ru/healthcheck/internal/contract"
	"i-sphere.ru/healthcheck/internal/env"
	"i-sphere.ru/healthcheck/internal/storage"
)

type Server struct {
	worker
	healthchecks []contract.Healthchecker
	info         *env.ClusterInfo
	memory       *storage.Memory
}

func NewServer(healthchecks []contract.Healthchecker, info *env.ClusterInfo, memory *storage.Memory) *Server {
	return &Server{
		healthchecks: healthchecks,
		info:         info,
		memory:       memory,
	}
}

func (s *Server) Run(ctx context.Context) error {
	return s.runDestination(ctx, s.memory, s.info, s.healthchecks, contract.HealthcheckDestinationServer)
}
