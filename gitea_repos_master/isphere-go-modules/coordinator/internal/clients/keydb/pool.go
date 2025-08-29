package keydb

import (
	"context"
	"fmt"
	"sync"

	"gitea-http.gitea.svc.cluster.local/isphere-go-modules/coordinator/internal/config"
	"github.com/redis/go-redis/v9"
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

	log := logrus.WithContext(ctx).WithField("addr", p.cfg.Services.KeyDB.Addr)

	if p.client != nil {
		if err := p.client.Ping(ctx).Err(); err != nil {
			log.WithError(err).Error("failed to ping keydb connection")

			p.client = nil
		} else {
			log.Debug("acquire existing keydb connection")

			return p.client, nil
		}
	}

	log.Debug("create keydb connection")

	conn := redis.NewClient(&redis.Options{
		Addr:         p.cfg.Services.KeyDB.Addr,
		Password:     p.cfg.Services.KeyDB.Password,
		DB:           p.cfg.Services.KeyDB.Database,
		DialTimeout:  p.cfg.Services.KeyDB.DialTimeout,
		ReadTimeout:  p.cfg.Services.KeyDB.ReadTimeout,
		WriteTimeout: p.cfg.Services.KeyDB.WriteTimeout,
		PoolSize:     p.cfg.Services.KeyDB.PoolSize,
	})

	if err := conn.Ping(ctx).Err(); err != nil {
		log.WithError(err).Error("failed to ping keydb connection")

		//goland:noinspection GoUnhandledErrorResult
		conn.Close()

		return nil, fmt.Errorf("failed to ping keydb connection: %w", err)
	}

	go func() {
		<-ctx.Done()
		//goland:noinspection GoUnhandledErrorResult
		conn.Close()
	}()

	p.client = &Conn{conn}

	return p.client, nil
}
