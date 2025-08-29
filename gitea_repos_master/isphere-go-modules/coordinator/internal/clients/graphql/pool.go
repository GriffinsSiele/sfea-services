package graphql

import (
	"context"
	"net/http"
	"sync"

	"gitea-http.gitea.svc.cluster.local/isphere-go-modules/coordinator/internal/config"
	"github.com/hasura/go-graphql-client"
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
		log.Debug("acquire existing graphql connection")

		return p.connections[provider.Endpoint], nil
	}

	log.Debug("create graphql connection")

	graphQLClient := graphql.NewClient(provider.Endpoint, nil)

	if provider.HTTPBasic.Enabled {
		graphQLClient = graphQLClient.WithRequestModifier(func(request *http.Request) {
			request.SetBasicAuth(provider.HTTPBasic.Username, provider.HTTPBasic.Password)
		})
	}

	if len(provider.Headers) > 0 {
		graphQLClient = graphQLClient.WithRequestModifier(func(request *http.Request) {
			for _, header := range provider.Headers {
				request.Header.Add(header.Name, header.Value)
			}
		})
	}

	p.connections[provider.Endpoint] = &Conn{
		Client:   graphQLClient,
		Endpoint: provider.Endpoint,
	}

	return p.connections[provider.Endpoint], nil
}
