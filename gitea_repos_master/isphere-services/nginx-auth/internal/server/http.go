package server

import (
	"context"
	"fmt"
	"log/slog"
	"net/http"

	"i-sphere.ru/nginx-auth/internal/configuration"
	"i-sphere.ru/nginx-auth/internal/handler"
)

type HTTP struct {
	handler *handler.HTTP
	params  *configuration.Params
}

func NewHTTP(handler *handler.HTTP, params *configuration.Params) *HTTP {
	return &HTTP{
		handler: handler,
		params:  params,
	}
}

func (s *HTTP) ListenAndServe(ctx context.Context) error {
	httpServer := http.Server{
		Addr:    s.params.HTTP.Addr(),
		Handler: s.handler,
	}

	slog.With("http.host", s.params.HTTP.Host, "http.port", s.params.HTTP.Port).InfoContext(ctx, "starting HTTP server")

	if err := httpServer.ListenAndServe(); err != nil {
		return fmt.Errorf("failed to serve: %w", err)
	}

	if err := httpServer.Shutdown(ctx); err != nil {
		return fmt.Errorf("failed to shutdown: %w", err)
	}

	return nil
}
