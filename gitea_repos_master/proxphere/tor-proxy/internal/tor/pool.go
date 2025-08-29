package tor

import (
	"bytes"
	"context"
	"fmt"
	"log/slog"
	"net"
	"os"
	"sync"
	"sync/atomic"
	"text/template"

	"github.com/cretz/bine/tor"
	"go.uber.org/fx"
	"i-sphere.ru/proxphere/tor-proxy/internal/config"
)

type Pool struct {
	*tor.Tor
	items   []*tor.Tor
	dialers []*tor.Dialer
	rw      sync.RWMutex
}

func NewPool(
	lc fx.Lifecycle,
	poolSize config.PoolSize,
	entryNodes config.EntryNodes,
	exitNodes config.ExitNodes,
	excludeNodes config.ExcludeNodes,
	maxMemInQueuesMB config.MaxMemInQueuesMB,
) (*Pool, error) {
	torRC, err := createTorRCFile(entryNodes, exitNodes, excludeNodes, maxMemInQueuesMB)
	if err != nil {
		return nil, fmt.Errorf("failed to create torrc file: %w", err)
	}

	p := &Pool{
		items:   make([]*tor.Tor, 0, poolSize),
		dialers: make([]*tor.Dialer, 0, poolSize),
	}

	ctx, cancel := context.WithCancelCause(context.Background())

	go func() {
		for i := range poolSize {
			go func(i int) {
				startConf := &tor.StartConf{
					TempDataDirBase: os.TempDir(),
					TorrcFile:       torRC.Name(),
					DebugWriter:     NewLogWriter(slog.Default().With("index", i)),
				}

				t, err := tor.Start(ctx, startConf)
				if err != nil {
					cancel(err)
					return
				}

				dialer, err := t.Dialer(ctx, nil)
				if err != nil {
					//goland:noinspection GoUnhandledErrorResult
					t.Close()
					cancel(err)
					return
				}

				p.rw.Lock()
				p.items = append(p.items, t)
				p.dialers = append(p.dialers, dialer)
				p.rw.Unlock()
			}(int(i))
		}
	}()

	lc.Append(fx.Hook{
		OnStop: func(ctx context.Context) error {
			defer cancel(context.Cause(ctx))
			var wg sync.WaitGroup
			for i := range p.items {
				wg.Add(1)
				go func(i int) {
					//goland:noinspection GoUnhandledErrorResult
					p.items[i].Close()
				}(i)
			}
			wg.Wait()
			return nil
		},
	})

	return p, nil
}

func (p *Pool) Acquire(ctx context.Context, network, addr string) (net.Conn, error) {
	p.rw.RLock()
	defer p.rw.RUnlock()

	connCh := make(chan net.Conn, 1)
	var exit atomic.Bool

	for i := range p.dialers {
		go func(i int) {
			conn, err := p.dialers[i].DialContext(ctx, network, addr)
			if err != nil {
				return
			}

			if exit.Load() {
				//goland:noinspection GoUnhandledErrorResult
				conn.Close()
				return
			}

			connCh <- conn
		}(i)
	}

	defer func() {
		exit.Store(true)
	}()

	select {
	case conn := <-connCh:
		exit.Store(true)
		return conn, nil
	case <-ctx.Done():
		return nil, context.Cause(ctx)
	}
}

func createTorRCFile(
	entryNodes config.EntryNodes,
	exitNodes config.ExitNodes,
	excludeNodes config.ExcludeNodes,
	maxMemInQueuesMB config.MaxMemInQueuesMB,
) (*os.File, error) {
	torRCTmplBytes, err := os.ReadFile("configs/torrc.tmpl")
	if err != nil {
		return nil, fmt.Errorf("failed to open torrc template: %w", err)
	}

	torRCTmpl, err := template.New("torrc").Parse(string(torRCTmplBytes))
	if err != nil {
		return nil, fmt.Errorf("failed to parse torrc template: %w", err)
	}

	var torRCBuf bytes.Buffer
	if err = torRCTmpl.Execute(&torRCBuf, map[string]any{
		"EntryNodes":       entryNodes,
		"ExitNodes":        exitNodes,
		"ExcludeNodes":     excludeNodes,
		"MaxMemInQueuesMB": maxMemInQueuesMB,
	}); err != nil {
		return nil, fmt.Errorf("failed to render torrc template: %w", err)
	}

	torRC, err := os.CreateTemp(os.TempDir(), "torrc")
	if err != nil {
		return nil, fmt.Errorf("failed to create torrc file: %w", err)
	}

	if _, err = torRC.Write(torRCBuf.Bytes()); err != nil {
		return nil, fmt.Errorf("failed to write torrc file: %w", err)
	}

	return torRC, nil
}
