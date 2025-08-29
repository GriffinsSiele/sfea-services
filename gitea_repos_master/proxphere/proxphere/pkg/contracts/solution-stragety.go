package contracts

import (
	"context"

	"go.i-sphere.ru/proxy/pkg/models"
)

type SolutionStrategy interface {
	Name() string
	Reorder(context.Context, []*models.ProxySpec, ...string) ([]*models.ProxySpec, error)
}

const SolutionStrategyGroupTag string = `group:"solution-strategies"`

type DefaultSolutionStrategy interface {
	SolutionStrategy
}

type SolutionStrategyWithParams struct {
	SolutionStrategy SolutionStrategy
	Params           []string
}
