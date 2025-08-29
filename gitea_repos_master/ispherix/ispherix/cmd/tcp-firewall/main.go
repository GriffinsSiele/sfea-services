package main

import (
	"context"
	"log/slog"
	"os"
	"os/signal"
	"runtime"
	"syscall"

	_ "go.i-sphere.ru/ispherix/internal"
	"go.i-sphere.ru/ispherix/internal/clickhouse"
	"go.i-sphere.ru/ispherix/internal/geoip"
	"go.i-sphere.ru/ispherix/internal/tcp/firewall"
)

func main() {
	runtime.GOMAXPROCS(runtime.NumCPU())

	ctx, cancel := context.WithCancel(context.Background())
	defer cancel()

	ch := make(chan os.Signal, 1)
	signal.Notify(ch, os.Interrupt, syscall.SIGTERM)

	go func() {
		<-ch
		cancel()
	}()

	geoipDatabase, err := geoip.NewDatabase()
	if err != nil {
		slog.ErrorContext(ctx, "failed to create geoip database", "error", err)
		os.Exit(1)
	}

	f := firewall.NewFirewall(
		firewall.NewHandler(
			clickhouse.NewPool(),
			geoipDatabase,
		),
	)

	if err := f.Listen(ctx); err != nil {
		slog.ErrorContext(ctx, "failed to listen tcp-firewall", "error", err)
		os.Exit(1)
	}
}
