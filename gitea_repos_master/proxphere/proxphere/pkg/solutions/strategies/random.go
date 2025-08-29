package strategies

import (
	"context"
	"fmt"

	"go.i-sphere.ru/proxy/pkg/models"
	"go.i-sphere.ru/proxy/pkg/utils"
)

type Random struct {
}

func NewRandom() *Random {
	return new(Random)
}

func (f *Random) Name() string {
	return "random"
}

func (f *Random) Reorder(_ context.Context, proxies []*models.ProxySpec, params ...string) ([]*models.ProxySpec, error) {
	if err := utils.Shuffle(proxies); err != nil {
		return nil, fmt.Errorf("failed to shuffle proxies: %w", err)
	}
	limit := utils.FirstParamAsIntWithMax(len(proxies), params...)
	if limit == 0 {
		limit = 1
	}
	return proxies[0:limit], nil
}
