package strategies

import (
	"context"

	"go.i-sphere.ru/proxy/pkg/models"
	"go.i-sphere.ru/proxy/pkg/utils"
)

type Top struct {
}

func NewTop() *Top {
	return new(Top)
}

func (f *Top) Name() string {
	return "top"
}

func (f *Top) Reorder(_ context.Context, proxies []*models.ProxySpec, params ...string) ([]*models.ProxySpec, error) {
	if len(proxies) == 0 {
		return nil, nil
	}

	limit := utils.FirstParamAsIntWithMax(len(proxies), params...)
	if limit == 0 {
		limit = 1
	}
	return proxies[0:limit], nil
}
