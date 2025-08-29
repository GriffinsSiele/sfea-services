package cli

import (
	"context"
	"fmt"
	"log/slog"
	"slices"
	"sync"
	"time"

	"i-sphere.ru/healthcheck/internal/contract"
	"i-sphere.ru/healthcheck/internal/env"
	"i-sphere.ru/healthcheck/internal/storage"
)

type worker struct{}

func (w *worker) runDestination(
	ctx context.Context,
	memory *storage.Memory,
	info *env.ClusterInfo,
	healthchecks []contract.Healthchecker,
	destination contract.HealthcheckDestination,
) error {
	errCh := make(chan error)
	var wg sync.WaitGroup
	var rw sync.RWMutex

	for i := range healthchecks {
		if !slices.Contains(healthchecks[i].Destinations(), destination) {
			continue
		}

		wg.Add(1)

		go func(i int) {
			rw.RLock()
			healthcheck := healthchecks[i]
			rw.RUnlock()

			defer wg.Done()

			log := slog.With("healthcheck", healthcheck.Name())
			log.With("inspection_interval", healthcheck.InspectionInterval()).
				InfoContext(ctx, "watch inspection")

			for {
				events := storage.NewEvents(healthcheck.Name(), info.NodeName, info.Hostname)
				err := healthcheck.Check(ctx, events)
				events.Duration = time.Since(events.CreatedAt)
				if err != nil {
					log.With("error", err).ErrorContext(ctx, "healthcheck failed")
					events.Error = err.Error()
				} else {
					log.DebugContext(ctx, "healthcheck passed")
				}

				memory.Store(events)
				time.Sleep(healthcheck.InspectionInterval())
			}
		}(i)
	}

	for err := range errCh {
		return fmt.Errorf("unexpected error: %w", err)
	}
	wg.Wait()

	return nil
}
