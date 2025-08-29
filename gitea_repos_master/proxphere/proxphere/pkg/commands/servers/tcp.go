package servers

import (
	"errors"
	"fmt"
	"net"
	"os"

	"github.com/charmbracelet/log"
	"github.com/urfave/cli/v2"

	"go.i-sphere.ru/proxy/pkg/handlers"
)

type TCP struct {
	handler *handlers.TCP

	log *log.Logger
}

func NewTCP(handler *handlers.TCP) *TCP {
	return &TCP{
		handler: handler,

		log: log.WithPrefix("servers.TCP"),
	}
}

func (t *TCP) NewCommand() *cli.Command {
	return &cli.Command{
		Category: "server",
		Name:     "server/tcp",
		Action:   t.Start,
	}
}

func (t *TCP) Start(c *cli.Context) error {
	addr := net.JoinHostPort(os.Getenv("TCP_SERVER_HOST"), os.Getenv("TCP_SERVER_PORT"))
	listener, err := net.Listen("tcp", addr)
	if err != nil {
		return fmt.Errorf("failed to listen: %w", err)
	}
	//goland:noinspection GoUnhandledErrorResult
	defer listener.Close()

	t.log.With("addr", addr).Info("listening server")

	for {
		conn, err := listener.Accept()
		if err != nil {
			if errors.Is(err, net.ErrClosed) {
				break
			}
			t.log.With("error", err).Error("accept error")
			continue
		}
		go t.handler.HandleWithScheme(c.Context, conn, "http")
	}

	return nil
}
