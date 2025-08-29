package servers

import (
	"context"
	"fmt"
	"sync"

	"github.com/charmbracelet/log"
	"github.com/urfave/cli/v2"

	"go.i-sphere.ru/proxy/pkg/contracts"
)

type All struct {
	servers []contracts.Server

	log *log.Logger
}

func NewAll(servers []contracts.Server) *All {
	return &All{
		servers: servers,
		log:     log.WithPrefix("servers.all"),
	}
}

func (a *All) NewCommand() *cli.Command {
	return &cli.Command{
		Category: "server",
		Name:     "server",
		Action:   a.Start,
	}
}

func (a *All) Start(c *cli.Context) error {
	cancelCtx, cancel := context.WithCancelCause(c.Context)
	defer cancel(nil)

	var wg sync.WaitGroup
	for _, server := range a.servers {
		wg.Add(1)
		go func(s contracts.Server) {
			defer wg.Done()
			if err := s.Start(c); err != nil {
				a.log.With("error", err).Error("server error")
				cancel(err)
			}
		}(server)
	}

	done := make(chan any)
	go func() {
		wg.Wait()
		close(done)
	}()

	select {
	case <-cancelCtx.Done():
		if err := cancelCtx.Err(); err != nil {
			return fmt.Errorf("failed to start servers: %w", err)
		}
	case <-done:
	}

	return nil
}
