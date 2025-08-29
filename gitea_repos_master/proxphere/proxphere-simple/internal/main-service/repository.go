package main_service

import (
	"context"
	"encoding/json"
	"fmt"
	"log/slog"
	"net/http"
	"net/url"
	"slices"
	"sync"
	"time"
)

type Repository struct {
	options []*Option

	defaultUpdateTimeout time.Duration
	rw                   sync.RWMutex
}

func NewRepository() *Repository {
	return &Repository{
		defaultUpdateTimeout: 1 * time.Minute,
	}
}

func (r *Repository) FindByGroups(_ context.Context, groups ...int) ([]*Option, error) {
	options := make([]*Option, 0, len(r.options))
	for _, option := range r.options {
		if slices.Contains(groups, option.ProxyGroup) {
			options = append(options, option)
		}
	}

	return options, nil
}

func (r *Repository) Listen(ctx context.Context) error {
	if err := r.Update(ctx); err != nil {
		return fmt.Errorf("failed to init options: %w", err)
	}

	slog.InfoContext(ctx, "listening on options updates", "every", r.defaultUpdateTimeout)

	for {
		select {
		case <-time.After(r.defaultUpdateTimeout):
			if err := r.Update(ctx); err != nil {
				return fmt.Errorf("failed to update options: %w", err)
			}

		case <-ctx.Done():
			return ctx.Err()
		}
	}
}

func (r *Repository) Update(ctx context.Context) error {
	u := url.URL{
		Scheme: "http",
		Host:   "get-proxies-master.main-service.svc.cluster.local",
		Path:   "/2.00/get_proxies.php",
		RawQuery: url.Values{
			"status": {"1"},
		}.Encode(),
	}

	req, err := http.NewRequestWithContext(ctx, http.MethodGet, u.String(), http.NoBody)
	if err != nil {
		return fmt.Errorf("failed to create update request: %w", err)
	}

	resp, err := http.DefaultClient.Do(req)
	if err != nil {
		return fmt.Errorf("failed to perform request of update options: %w", err)
	}
	//goland:noinspection GoUnhandledErrorResult
	defer resp.Body.Close()

	r.rw.Lock()
	defer r.rw.Unlock()

	if err = json.NewDecoder(resp.Body).Decode(&r.options); err != nil {
		return fmt.Errorf("failed to decode response: %w", err)
	}

	slog.InfoContext(ctx, "options updated", "count", len(r.options))

	return nil
}
