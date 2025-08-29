package solutions

import (
	"context"
	"fmt"
	"strings"

	http "github.com/Danny-Dasilva/fhttp"
	"go.i-sphere.ru/proxy/pkg/contracts"
	"go.i-sphere.ru/proxy/pkg/models"
	"go.i-sphere.ru/proxy/pkg/trackers"
	"go.i-sphere.ru/proxy/pkg/utils"
)

type ProxySpec struct {
	defaultStrategy contracts.DefaultSolutionStrategy
	strategies      []contracts.SolutionStrategy
}

func NewProxySpec(defaultStrategy contracts.DefaultSolutionStrategy, strategies []contracts.SolutionStrategy) *ProxySpec {
	return &ProxySpec{
		defaultStrategy: defaultStrategy,
		strategies:      strategies,
	}
}

func (p *ProxySpec) GetStrategiesFromRequest(ctx context.Context, req *http.Request) ([]*contracts.SolutionStrategyWithParams, error) {
	tracer, closer := trackers.MustTracerCloser()
	defer trackers.MustClose(closer)

	ctx, span := trackers.StartSpanWithContext(ctx, tracer, "find strategies in request")
	defer span.Finish()

	commandLine := utils.RequestParam(req, contracts.XSphereProxySpecStrategy)
	span.LogKV("command_line", commandLine)
	if commandLine == "" {
		span.LogKV("added_strategy", p.defaultStrategy)
		return []*contracts.SolutionStrategyWithParams{{SolutionStrategy: p.defaultStrategy}}, nil
	}

	commandStrings := strings.Split(commandLine, "|")
	strategies := make([]*contracts.SolutionStrategyWithParams, 0, len(commandStrings))

	for _, commandString := range commandStrings {
		commandString = strings.TrimSpace(commandString)

		var strategy contracts.SolutionStrategy
		var params []string

		name, rest, _ := strings.Cut(commandString, "(")
		name = strings.TrimSpace(name)

		dirtyParamsLine, _, _ := strings.Cut(rest, ")")
		dirtyParams := strings.Split(dirtyParamsLine, ",")
		for _, dirtyParam := range dirtyParams {
			params = append(params, strings.TrimSpace(dirtyParam))
		}

		for _, s := range p.strategies {
			if s.Name() == name {
				strategy = s
				break
			}
		}

		if strategy == nil {
			return nil, trackers.Fail(span, fmt.Errorf("unknown strategy: %s", name))
		}

		strategies = append(strategies, &contracts.SolutionStrategyWithParams{
			SolutionStrategy: strategy,
			Params:           params,
		})
		span.LogKV("added_strategy", strategy)
		if len(params) > 0 {
			span.LogKV("added_strategy_params", params)
		}
	}

	return strategies, nil
}

func (p *ProxySpec) SelectFromWithStrategy(ctx context.Context, proxies []*models.ProxySpec, strategies []*contracts.SolutionStrategyWithParams) ([]*models.ProxySpec, error) {
	elems := make([]*models.ProxySpec, len(proxies))
	copy(elems, proxies)

	var err error
	for _, strategy := range strategies {
		if elems, err = strategy.SolutionStrategy.Reorder(ctx, elems, strategy.Params...); err != nil {
			return nil, fmt.Errorf("failed to reorder proxies: %w", err)
		}
		if len(elems) == 0 {
			return nil, nil
		}
	}

	return elems, nil
}
