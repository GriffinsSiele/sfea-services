package prom

import (
	"bytes"
	"fmt"
	"slices"
	"strings"

	"github.com/prometheus/client_golang/prometheus"
	"github.com/prometheus/common/expfmt"
	"i-sphere.ru/healthcheck/internal/storage"
	"i-sphere.ru/healthcheck/internal/util"
)

type Marshaller struct{}

func (m *Marshaller) Marshal(v []*storage.MemoryLog) ([]byte, error) {
	registry := prometheus.NewRegistry()

	collectors := make(map[string]*prometheus.GaugeVec)

	slices.SortStableFunc(v, func(a, b *storage.MemoryLog) int {
		if a.CreatedAt.Before(b.CreatedAt) {
			return -1
		}
		if a.CreatedAt.After(b.CreatedAt) {
			return 1
		}
		return 0
	})

	for _, l := range v {
		for _, event := range l.Events {
			name := fmt.Sprintf("healthcheck_%s_%s", normalizeKey(l.Name), normalizeKey(event.Name))
			if event.Error != "" {
				name += "_error"
			}

			collector, ok := collectors[name]
			if !ok {
				labels := []string{
					"node_name",
					"hostname",
					"subject",
				}
				if event.Error != "" {
					labels = append(labels, "error")
				}
				collector = prometheus.NewGaugeVec(prometheus.GaugeOpts{Name: name}, labels)

				if err := registry.Register(collector); err != nil {
					return nil, fmt.Errorf("failed to register collector: %w", err)
				}

				collectors[name] = collector
			}

			labels := prometheus.Labels{
				"node_name": l.NodeName,
				"hostname":  l.Hostname,
				"subject":   event.Subject,
			}
			if event.Error != "" {
				labels["error"] = event.Error
			}
			collectors[name].With(labels).Set(float64(util.MustDuration(event.Duration).Nanoseconds()))
		}
	}

	msf, err := registry.Gather()
	if err != nil {
		return nil, fmt.Errorf("failed to gather metrics: %w", err)
	}

	var buf bytes.Buffer
	enc := expfmt.NewEncoder(&buf, expfmt.FmtText)
	for _, mf := range msf {
		if err := enc.Encode(mf); err != nil {
			return nil, fmt.Errorf("failed to encode metrics: %w", err)
		}
	}

	return buf.Bytes(), nil
}

func normalizeKey(v string) string {
	return strings.ReplaceAll(strings.ToLower(v), "-", "_")
}
