package repositories

import (
	"context"
	"encoding/json"
	"fmt"
	"net"
	"net/url"
	"strconv"
	"sync"
	"time"

	http "github.com/Danny-Dasilva/fhttp"
	"go.i-sphere.ru/proxy/pkg/clients"
	"go.i-sphere.ru/proxy/pkg/models"
)

type ProxySpec struct {
	mainService *clients.MainService

	items []*models.ProxySpec
	mu    sync.Mutex
}

func NewProxySpec(mainService *clients.MainService) (*ProxySpec, error) {
	p := &ProxySpec{
		mainService: mainService,
	}

	cancelCtx, cancel := context.WithTimeout(context.Background(), 30*time.Second)
	defer cancel()

	items, err := p.loadAll(cancelCtx)
	if err != nil {
		return nil, fmt.Errorf("failed to find proxies: %w", err)
	}
	p.items = items

	return p, nil
}

func (p *ProxySpec) Listen(ctx context.Context) {
	for {
		select {
		case <-ctx.Done():
			return
		case <-time.After(5 * time.Second):
			if items, err := p.loadAll(ctx); err == nil {
				p.mu.Lock()
				p.items = items
				p.mu.Unlock()
			}
		}
	}
}

func (p *ProxySpec) FindAll(context.Context) ([]*models.ProxySpec, error) {
	return p.items, nil
}

func (p *ProxySpec) Find(ctx context.Context, id int) (*models.ProxySpec, error) {
	for _, ps := range p.items {
		if int(ps.ID) == id {
			return ps, nil
		}
	}

	params := url.Values{
		"id": {strconv.Itoa(id)},
	}

	req, err := p.mainService.NewRequest(ctx, http.MethodGet, "/get_proxies.php?"+params.Encode(), http.NoBody)
	if err != nil {
		return nil, fmt.Errorf("failed to create request: %w", err)
	}

	resp, err := http.DefaultClient.Do(req)
	if err != nil {
		return nil, fmt.Errorf("failed to execute request: %w", err)
	}

	var response mainServiceGetProxiesResponse
	if err = json.NewDecoder(resp.Body).Decode(&response); err != nil {
		return nil, fmt.Errorf("failed to decode response: %w", err)
	}

	if len(response) == 0 {
		return nil, fmt.Errorf("proxy with id %d not found", id)
	}
	return response[0].Convert()
}

func (p *ProxySpec) FindOneByGroupID(ctx context.Context, groupID int) (*models.ProxySpec, error) {
	proxies, err := p.FindByGroupID(ctx, groupID)
	if err != nil {
		return nil, fmt.Errorf("failed to find proxies by group id: %w", err)
	}
	for _, ps := range proxies {
		return ps, nil
	}
	return nil, fmt.Errorf("proxy with group id %d not found", groupID)
}

func (p *ProxySpec) FindByGroupID(_ context.Context, groupID int) ([]*models.ProxySpec, error) {
	var proxies []*models.ProxySpec
	for _, ps := range p.items {
		if g := ps.Group; g != nil && int(g.ID) == groupID {
			proxies = append(proxies, ps)
		}
	}
	return proxies, nil
}

func (p *ProxySpec) FindOneByCountryCode(ctx context.Context, countryCode string) (*models.ProxySpec, error) {
	proxies, err := p.FindByCountryCode(ctx, countryCode)
	if err != nil {
		return nil, fmt.Errorf("failed to find proxies by group id: %w", err)
	}
	for _, ps := range proxies {
		return ps, nil
	}
	return nil, fmt.Errorf("proxy with country code %s not found", countryCode)
}

func (p *ProxySpec) FindByCountryCode(_ context.Context, countryCode string) ([]*models.ProxySpec, error) {
	var proxies []*models.ProxySpec
	for _, ps := range p.items {
		if ps.CountryCode == countryCode {
			proxies = append(proxies, ps)
		}
	}
	return proxies, nil
}

func (p *ProxySpec) loadAll(ctx context.Context) ([]*models.ProxySpec, error) {
	req, err := p.mainService.NewRequest(ctx, http.MethodGet, "/get_proxies.php", http.NoBody)
	if err != nil {
		return nil, fmt.Errorf("failed to create request: %w", err)
	}

	resp, err := http.DefaultClient.Do(req)
	if err != nil {
		return nil, fmt.Errorf("failed to execute request: %w", err)
	}

	var response mainServiceGetProxiesResponse
	if err = json.NewDecoder(resp.Body).Decode(&response); err != nil {
		return nil, fmt.Errorf("failed to decode response: %w", err)
	}

	proxies := make([]*models.ProxySpec, 0, len(response))
	for _, item := range response {
		if item.Status != mainServiceProxyStatusEnabled {
			continue
		}
		ps, psErr := item.Convert()
		if psErr != nil {
			return nil, fmt.Errorf("failed to convert item: %w", psErr)
		}
		proxies = append(proxies, ps)
	}

	return proxies, nil
}

type mainServiceGetProxiesResponse []mainServiceGetProxiesResponseItem

type mainServiceGetProxiesResponseItem struct {
	ID           int                    `json:"id,string"`
	Server       string                 `json:"server"`
	Port         int                    `json:"port,string"`
	Login        string                 `json:"login"`
	Password     string                 `json:"password"`
	Country      string                 `json:"country"`
	ProxyGroupID int                    `json:"proxygroup,string"`
	Status       mainServiceProxyStatus `json:"status,string"`
}

type mainServiceProxyStatus int

const (
	mainServiceProxyStatusEnabled  mainServiceProxyStatus = 1
	mainServiceProxyStatusDisabled mainServiceProxyStatus = 0
)

func (i *mainServiceGetProxiesResponseItem) Convert() (*models.ProxySpec, error) {
	var p models.ProxySpec

	p.ID = models.StringifyInt(i.ID)
	p.Group = &models.ProxySpecGroup{
		ID: models.StringifyInt(i.ProxyGroupID),
	}

	//goland:noinspection HttpUrlsUsage
	if u, err := url.Parse("http://" + net.JoinHostPort(i.Server, strconv.Itoa(i.Port))); err != nil {
		return nil, fmt.Errorf("failed to parse url: %w", err)
	} else {
		if i.Login != "" || i.Password != "" {
			u.User = url.UserPassword(i.Login, i.Password)
		}

		p.URL = u
	}

	p.CountryCode = i.Country

	return &p, nil
}
