package repository

import (
	"context"

	"gitea-http.gitea.svc.cluster.local/isphere-go-modules/coordinator/internal/config"
	"gitea-http.gitea.svc.cluster.local/isphere-go-modules/coordinator/internal/contract"
)

type ProviderRepository struct {
	cfg *config.Config
}

func NewProviderRepository(cfg *config.Config) *ProviderRepository {
	return &ProviderRepository{
		cfg: cfg,
	}
}

func (t *ProviderRepository) Find(_ context.Context, key string) (*config.Provider, error) {
	module, ok := t.cfg.Providers[key]
	if !ok {
		return nil, &contract.NotFoundError{Op: "provider", Key: key, Err: contract.ErrNotFound}
	}

	return module, nil
}
