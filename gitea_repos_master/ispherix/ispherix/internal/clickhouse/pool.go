package clickhouse

import (
	"context"
	"fmt"
	"sync"
)

type Pool struct {
	conn *Conn
	mu   sync.Mutex
}

func NewPool() *Pool {
	return &Pool{}
}

func (p *Pool) Acquire(ctx context.Context) (*Conn, error) {
	p.mu.Lock()

	if p.conn != nil {
		err := p.conn.Ping(ctx)
		p.mu.Unlock()
		if err == nil {
			return p.conn, nil
		}
		p.conn = nil
		return p.Acquire(ctx)
	}

	conn, err := NewConn()
	p.mu.Unlock()
	if err != nil {
		return nil, fmt.Errorf("failed to connect to clickhouse: %w", err)
	}
	p.conn = conn
	return p.Acquire(ctx)
}
