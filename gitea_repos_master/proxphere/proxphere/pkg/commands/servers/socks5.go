package servers

import (
	"context"
	"errors"
	"fmt"
	"net"
	"os"

	"github.com/charmbracelet/log"
	"github.com/urfave/cli/v2"
	"go.i-sphere.ru/proxy/pkg/contracts"

	"go.i-sphere.ru/proxy/pkg/handlers"
)

type SOCKS5 struct {
	handler *handlers.SOCKS5

	log *log.Logger
}

func NewSOCKS5(handler *handlers.SOCKS5) *SOCKS5 {
	return &SOCKS5{
		handler: handler,

		log: log.WithPrefix("servers.SOCKS5"),
	}
}

func (s *SOCKS5) NewCommand() *cli.Command {
	return &cli.Command{
		Category: "server",
		Name:     "server/socks5",
		Action:   s.Start,
	}
}

func (s *SOCKS5) Start(c *cli.Context) error {
	addr := net.JoinHostPort(os.Getenv("SOCKS5_SERVER_HOST"), os.Getenv("SOCKS5_SERVER_PORT"))
	listener, err := net.Listen("tcp", addr)
	if err != nil {
		return fmt.Errorf("failed to listen: %w", err)
	}
	//goland:noinspection GoUnhandledErrorResult
	defer listener.Close()
	contracts.OnStartServer(c.Context, s)

	s.log.With("addr", addr).Info("server started")

	for {
		cancelCtx, cancel := context.WithCancel(c.Context)
		select {
		case <-c.Context.Done():
			cancel()
			return c.Context.Err()
		default:
			conn, err := listener.Accept()
			if err != nil {
				cancel()
				if errors.Is(err, net.ErrClosed) {
					break
				}
				s.log.With("error", err).Error("accept error")
				continue
			}
			go func(ctx context.Context, conn net.Conn, cancel context.CancelFunc) {
				defer cancel()
				s.handler.Handle(ctx, conn)
			}(cancelCtx, conn, cancel)
		}
	}
}
