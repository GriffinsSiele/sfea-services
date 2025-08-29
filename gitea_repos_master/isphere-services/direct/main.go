package main

import (
	"context"
	"flag"
	"fmt"
	"log/slog"
	"os"
	"time"

	"i-sphere.ru/direct/internal"
)

func main() {
	ctx := context.Background()

	flag.DurationVar(&internal.K8sHandlerDuration, "k8s-handler-duration", 10*time.Second, "k8s handler duration")
	flag.Parse()

	if err := run(ctx); err != nil {
		slog.With("error", err).ErrorContext(ctx, "failed to run")
		os.Exit(1)
	}
}

func run(ctx context.Context) error {
	ctx, cancel := context.WithCancel(ctx)
	defer cancel()

	k8sConfig, err := internal.NewK8sConfig(ctx)
	if err != nil {
		return fmt.Errorf("failed to create k8s config: %w", err)
	}

	k8sClientSet, err := internal.NewK8sClientSetWithConfig(k8sConfig)
	if err != nil {
		return fmt.Errorf("failed to create k8s client set: %w", err)
	}

	k8sHandler := internal.NewK8sHandler(k8sClientSet, internal.K8sHandlerDuration)

	go func() {
		defer cancel()

		slog.With("duration", internal.K8sHandlerDuration).InfoContext(ctx, "starting k8s handler")
		if err := k8sHandler.Handle(ctx); err != nil {
			slog.With("error", err).ErrorContext(ctx, "failed to handle k8s")
		}
	}()

	dnsServer := internal.NewDNSServer()

	go func() {
		defer cancel()

		slog.With("addr", dnsServer.Addr).InfoContext(ctx, "starting DNS server")
		if err := dnsServer.ListenAndServe(); err != nil {
			slog.With("error", err).ErrorContext(ctx, "failed to start DNS server")
		}
	}()

	httpServer := internal.NewHTTPServer()

	go func() {
		defer cancel()

		slog.With("addr", httpServer.Addr).InfoContext(ctx, "starting HTTP server")
		if err := httpServer.ListenAndServe(); err != nil {
			slog.With("error", err).ErrorContext(ctx, "failed to start HTTP server")
		}
	}()

	go func() {
		<-ctx.Done()
		//goland:noinspection GoUnhandledErrorResult
		dnsServer.ShutdownContext(ctx)
		//goland:noinspection GoUnhandledErrorResult
		httpServer.Shutdown(ctx)
	}()

	<-ctx.Done()

	return nil
}
