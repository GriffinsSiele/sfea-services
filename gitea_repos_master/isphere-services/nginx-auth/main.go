package main

import (
	"context"
	"fmt"
	"log/slog"
	"os"

	"i-sphere.ru/nginx-auth/internal/configuration"
	"i-sphere.ru/nginx-auth/internal/handler"
	"i-sphere.ru/nginx-auth/internal/server"
	"i-sphere.ru/nginx-auth/internal/x"
)

func main() {
	ctx := context.Background()
	if err := run(ctx); err != nil {
		slog.With("err", err).ErrorContext(ctx, "failed to run")
		os.Exit(-1)
	}
}

func run(ctx context.Context) error {
	ctx, cancel := context.WithCancel(ctx)
	defer cancel()

	params, err := configuration.NewParams()
	if err != nil {
		return fmt.Errorf("failed to create params: %w", err)
	}

	geoIP, err := x.NewGeoIP(params)
	if err != nil {
		return fmt.Errorf("failed to create geoIP service: %w", err)
	}

	httpHandler := handler.NewHTTP(geoIP, params)

	errCh := make(chan error)

	go func() {
		httpsServer := server.NewHTTPS(httpHandler, params)
		if err := httpsServer.ListenAndServe(ctx); err != nil {
			errCh <- fmt.Errorf("failed to start HTTPS server: %w", err)
		}
	}()

	go func() {
		httpServer := server.NewHTTP(httpHandler, params)
		if err := httpServer.ListenAndServe(ctx); err != nil {
			errCh <- fmt.Errorf("failed to start HTTP server: %w", err)
		}
	}()

	for err := range errCh {
		return fmt.Errorf("failed to listen server: %w", err)
	}

	return nil
}
