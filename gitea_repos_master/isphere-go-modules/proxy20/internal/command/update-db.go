package commands

import (
	"context"
	"fmt"

	"i-sphere.ru/proxy/internal/repository"
)

type UpdateDB struct {
	proxyRepo *repository.Proxy
}

func NewUpdateDB(proxyRepo *repository.Proxy) *UpdateDB {
	return &UpdateDB{
		proxyRepo: proxyRepo,
	}
}

func (t *UpdateDB) Action(ctx context.Context) error {
	if err := t.proxyRepo.Update(ctx); err != nil {
		return fmt.Errorf("failed to update database: %w", err)
	}
	return nil
}
