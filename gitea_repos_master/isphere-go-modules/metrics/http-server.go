package main

import (
	"context"
	"fmt"
	"net/http"
	"os"

	"github.com/prometheus/client_golang/prometheus/promhttp"
	"k8s.io/klog/v2"
)

func serveHTTP(ctx context.Context) error {
	addr := os.Getenv("LISTEN_ADDRESS")
	handler := promhttp.Handler()

	server := &http.Server{
		Addr:    addr,
		Handler: handler,
	}

	go func() {
		if err := server.ListenAndServe(); err != nil {
			klog.ErrorS(err, "error in ListenAndServe")
		}
	}()

	<-ctx.Done()
	if err := server.Shutdown(ctx); err != nil {
		return fmt.Errorf("error in Shutdown: %w", err)
	}

	return nil
}
