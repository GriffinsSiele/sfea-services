package clickhouse

import (
	"context"
	"fmt"
	"sync"

	"gitea-http.gitea.svc.cluster.local/isphere-go-modules/coordinator/internal/config"
	"github.com/ClickHouse/clickhouse-go/v2"
	"github.com/sirupsen/logrus"
)

type Pool struct {
	cfg *config.Config

	client *Conn
	mu     sync.Mutex
}

func NewPool(cfg *config.Config) *Pool {
	return &Pool{
		cfg: cfg,
	}
}

func (p *Pool) Acquire(ctx context.Context) (*Conn, error) {
	p.mu.Lock()
	defer p.mu.Unlock()

	log := logrus.WithContext(ctx).WithField("addr", p.cfg.Services.Clickhouse.Addr)

	if p.client != nil {
		if err := p.client.Ping(ctx); err != nil {
			log.WithError(err).Error("failed to ping clickhouse connection")

			p.client = nil
		} else {
			log.Debug("acquire existing clickhouse connection")

			return p.client, nil
		}
	}

	log.Debug("create clickhouse connection")

	conn, err := clickhouse.Open(&clickhouse.Options{
		Debug:        p.cfg.Env == config.EnvDevelopment,
		Debugf:       logrus.Debugf,
		Addr:         []string{p.cfg.Services.Clickhouse.Addr},
		Settings:     map[string]any{"session_timezone": p.cfg.Services.Clickhouse.Timezone},
		MaxOpenConns: p.cfg.Services.Clickhouse.PoolSize,
		MaxIdleConns: p.cfg.Services.Clickhouse.PoolSize,
	})
	if err != nil {
		return nil, fmt.Errorf("failed to create clickhouse connection: %w", err)
	}

	if err = conn.Ping(ctx); err != nil {
		log.WithError(err).Error("failed to ping clickhouse connection")

		//goland:noinspection GoUnhandledErrorResult
		conn.Close()

		return nil, fmt.Errorf("failed to ping clickhouse connection: %w", err)
	}

	go func() {
		<-ctx.Done()
		//goland:noinspection GoUnhandledErrorResult
		conn.Close()
	}()

	p.client = &Conn{conn}

	return p.client, nil
}
