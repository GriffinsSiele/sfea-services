package main

import (
	"context"
	"fmt"

	"github.com/joho/godotenv"
	"github.com/prometheus/client_golang/prometheus"
	"golang.org/x/sync/errgroup"
	"k8s.io/klog/v2"
)

func init() {
	_ = godotenv.Load(".env")
	_ = godotenv.Overload(".env.local")
}

func main() {
	ctx := context.Background()
	ctx, cancel := context.WithCancel(ctx)
	defer cancel()

	rowsGauge := newClickhouseTotalRowsGauge()
	bytesGauge := newClickhouseTotalBytesGauge()
	prometheus.MustRegister(rowsGauge, bytesGauge)

	wg, ctx := errgroup.WithContext(ctx)

	wg.Go(func() error {
		if err := serveHTTP(ctx); err != nil {
			return fmt.Errorf("error in serveHTTP: %w", err)
		}

		return nil
	})

	wg.Go(func() error {
		if err := handleClickhouse(ctx, rowsGauge, bytesGauge); err != nil {
			return fmt.Errorf("error in handleClickhouse: %w", err)
		}

		return nil
	})

	if err := wg.Wait(); err != nil {
		klog.ErrorS(err, "error in Wait")
	}
}
