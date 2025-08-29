package tcp

import (
	"context"
	"fmt"
	"net/http"
	"net/url"
	"sync"

	"gitea-http.gitea.svc.cluster.local/isphere-go-modules/coordinator/internal/config"
	"github.com/sirupsen/logrus"
)

type Pool struct {
	connections map[string]*Conn
	mu          sync.Mutex
}

func NewPool() *Pool {
	return &Pool{
		connections: make(map[string]*Conn),
	}
}

func (p *Pool) Acquire(ctx context.Context, provider *config.Provider) (*Conn, error) {
	p.mu.Lock()
	defer p.mu.Unlock()

	log := logrus.WithContext(ctx).WithField("endpoint", provider.Endpoint)

	if p.connections[provider.Endpoint] != nil {
		log.Debug("acquire existing tcp connection")

		return p.connections[provider.Endpoint], nil
	}

	log.Debug("create tcp connection")

	endpoint, err := url.Parse(provider.Endpoint)
	if err != nil {
		return nil, fmt.Errorf("failed to parse provider endpoint url: %s: %w", provider.Endpoint, err)
	}

	p.connections[provider.Endpoint] = &Conn{
		Client:   http.DefaultClient,
		Endpoint: endpoint,
	}

	return p.connections[provider.Endpoint], nil
}
