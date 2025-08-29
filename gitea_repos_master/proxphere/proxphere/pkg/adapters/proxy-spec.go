package adapters

import (
	"context"
	"fmt"
	"strconv"

	http "github.com/Danny-Dasilva/fhttp"
	"go.i-sphere.ru/proxy/pkg/trackers"

	"go.i-sphere.ru/proxy/pkg/contracts"
	"go.i-sphere.ru/proxy/pkg/models"
	"go.i-sphere.ru/proxy/pkg/repositories"
	"go.i-sphere.ru/proxy/pkg/solutions"
	"go.i-sphere.ru/proxy/pkg/utils"
)

type ProxySpec struct {
	proxySpecRepo     *repositories.ProxySpec
	proxySpecSolution *solutions.ProxySpec
}

func NewProxySpec(proxySpecRepo *repositories.ProxySpec, proxySpecSolution *solutions.ProxySpec) *ProxySpec {
	return &ProxySpec{
		proxySpecRepo:     proxySpecRepo,
		proxySpecSolution: proxySpecSolution,
	}
}

func (p *ProxySpec) Default() *models.ProxySpec {
	return new(models.ProxySpec)
}

func (p *ProxySpec) SelectThroughStrategiesWithRequest(ctx context.Context, req *http.Request) ([]*models.ProxySpec, error) {
	tracer, closer := trackers.MustTracerCloser()
	defer trackers.MustClose(closer)

	ctx, span := trackers.StartSpanWithContext(ctx, tracer, "select proxy spec through strategies")
	defer span.Finish()

	proxySpecs, err := p.SelectWithRequest(req)
	if err != nil {
		return nil, trackers.Fail(span, fmt.Errorf("failed to select proxy spec: %w", err))
	}
	if len(proxySpecs) == 0 {
		return nil, nil
	}

	strategies, err := p.proxySpecSolution.GetStrategiesFromRequest(ctx, req)
	if err != nil {
		return nil, trackers.Fail(span, fmt.Errorf("failed to get strategies from request: %w", err))
	}

	filtered, err := p.proxySpecSolution.SelectFromWithStrategy(ctx, proxySpecs, strategies)
	if err != nil {
		return nil, trackers.Fail(span, fmt.Errorf("failed to decide on proxy spec: %w", err))
	}

	return filtered, nil
}

func (p *ProxySpec) SelectWithRequest(req *http.Request) ([]*models.ProxySpec, error) {
	// disable proxy feature completely
	disable := utils.RequestParam(req, contracts.XSphereProxySpecDisable)
	if disable != "" {
		return nil, nil
	}

	// select specific proxy spec
	idStr := utils.RequestParam(req, contracts.XSphereProxySpecID)
	if idStr != "" {
		id, err := strconv.Atoi(idStr)
		if err != nil {
			return nil, fmt.Errorf("failed to cast X-Sphere-Proxy-Spec-ID as int: %w", err)
		}
		proxySpec, err := p.proxySpecRepo.Find(req.Context(), id)
		if err != nil {
			return nil, fmt.Errorf("failed to find proxy spec by id: %w", err)
		}
		return []*models.ProxySpec{proxySpec}, nil
	}

	// select proxy spec by group
	groupIDStr := utils.RequestParam(req, contracts.XSphereProxySpecGroupID)
	if groupIDStr != "" {
		groupID, err := strconv.Atoi(groupIDStr)
		if err != nil {
			return nil, fmt.Errorf("failed to cast X-Sphere-Proxy-Spec-Group-ID as int: %w", err)
		}
		proxySpecs, err := p.proxySpecRepo.FindByGroupID(req.Context(), groupID)
		if err != nil {
			return nil, fmt.Errorf("failed to find proxy spec by group id: %w", err)
		}
		return proxySpecs, nil
	}

	// select proxy spec by country
	countryCode := utils.RequestParam(req, contracts.XSphereProxySpecCountryCode)
	if countryCode != "" {
		proxySpecs, err := p.proxySpecRepo.FindByCountryCode(req.Context(), countryCode)
		if err != nil {
			return nil, fmt.Errorf("failed to find proxy spec by country code: %w", err)
		}
		return proxySpecs, nil
	}

	return nil, nil
}
