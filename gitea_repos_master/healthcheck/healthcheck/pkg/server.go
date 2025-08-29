package pkg

import (
	"net/http"

	"github.com/prometheus/client_golang/prometheus/promhttp"
)

func NewServer() (*http.Server, error) {
	http.Handle("/metrics", promhttp.Handler())
	return &http.Server{}, nil
}
