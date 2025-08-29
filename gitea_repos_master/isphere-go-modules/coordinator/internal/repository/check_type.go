package repository

import (
	"context"

	"gitea-http.gitea.svc.cluster.local/isphere-go-modules/coordinator/internal/config"
	"gitea-http.gitea.svc.cluster.local/isphere-go-modules/coordinator/internal/contract"
)

type CheckTypeRepository struct {
	cfg *config.Config
}

func NewCheckTypeRepository(cfg *config.Config) *CheckTypeRepository {
	return &CheckTypeRepository{
		cfg: cfg,
	}
}

func (t *CheckTypeRepository) Find(_ context.Context, key string) (*config.CheckType, error) {
	for name, checkType := range t.cfg.CheckTypes {
		switch {
		case name == key,
			checkType.Upstream.RabbitMQ.Enabled &&
				checkType.Upstream.RabbitMQ.Scope == key,
			checkType.Upstream.RabbitMQ.Enabled &&
				checkType.Upstream.RabbitMQ.Async.Enabled &&
				checkType.Upstream.RabbitMQ.Async.Scope == key:
			return checkType, nil
		}
	}

	return nil, &contract.NotFoundError{Op: "check_type", Key: key, Err: contract.ErrNotFound}
}
