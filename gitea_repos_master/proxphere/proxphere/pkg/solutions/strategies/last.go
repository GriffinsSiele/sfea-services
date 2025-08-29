package strategies

import (
	"context"

	"go.i-sphere.ru/proxy/pkg/models"
	"go.i-sphere.ru/proxy/pkg/utils"
)

type Last struct {
}

func NewLast() *Last {
	return new(Last)
}

func (f *Last) Name() string {
	return "last"
}

func (f *Last) Reorder(_ context.Context, proxies []*models.ProxySpec, params ...string) ([]*models.ProxySpec, error) {
	if len(proxies) == 0 {
		return nil, nil
	}

	limitFromEnd := utils.FirstParamAsIntWithMax(len(proxies), params...)
	if limitFromEnd == 0 {
		limitFromEnd = 1
	}
	return proxies[len(proxies)-limitFromEnd:], nil
}
