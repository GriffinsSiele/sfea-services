package servers

import (
	"context"
	"crypto/tls"
	"crypto/x509"
	"errors"
	"fmt"
	"net"
	"os"

	"github.com/charmbracelet/log"
	"github.com/urfave/cli/v2"
	"go.i-sphere.ru/proxy/pkg/contracts"

	"go.i-sphere.ru/proxy/pkg/handlers"
)

type TLS struct {
	handler *handlers.TCP

	log *log.Logger
}

func NewTLS(handler *handlers.TCP) *TLS {
	return &TLS{
		handler: handler,

		log: log.WithPrefix("servers.TLS"),
	}
}

func (t *TLS) NewCommand() *cli.Command {
	return &cli.Command{
		Category: "server",
		Name:     "server/tls",
		Action:   t.Start,
	}
}

func (t *TLS) Start(c *cli.Context) error {
	certificate, err := tls.LoadX509KeyPair(os.Getenv("TLS_SERVER_CERT_FILE"), os.Getenv("TLS_SERVER_PRIVATE_KEY_FILE"))
	if err != nil {
		return fmt.Errorf("failed to load TLS certificate: %w", err)
	}
	caCertPool, err := t.loadCACertPool(os.Getenv("TLS_CA_CERT_FILE"))
	if err != nil {
		return fmt.Errorf("failed to load CA cert pool: %w", err)
	}
	config := &tls.Config{
		Certificates: []tls.Certificate{
			certificate,
		},
		ClientCAs: caCertPool,
	}

	addr := net.JoinHostPort(os.Getenv("TLS_SERVER_HOST"), os.Getenv("TLS_SERVER_PORT"))
	listener, err := tls.Listen("tcp", addr, config)
	if err != nil {
		return fmt.Errorf("failed to listen: %w", err)
	}
	//goland:noinspection GoUnhandledErrorResult
	defer listener.Close()
	contracts.OnStartServer(c.Context, t)

	t.log.With("addr", listener.Addr()).Info("listening server")

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
				t.log.With("error", err).Error("accept error")
				continue
			}
			go func(ctx context.Context, conn net.Conn, cancel context.CancelFunc) {
				defer cancel()
				t.handler.HandleWithScheme(cancelCtx, conn, "https")
			}(cancelCtx, conn, cancel)
		}
	}
}

func (t *TLS) loadCACertPool(caCertFile string) (*x509.CertPool, error) {
	caCertPool := x509.NewCertPool()
	caCertBytes, err := os.ReadFile(caCertFile)
	if err != nil {
		return nil, fmt.Errorf("failed to read CA cert: %w", err)
	}
	if !caCertPool.AppendCertsFromPEM(caCertBytes) {
		return nil, errors.New("failed to append CA cert")
	}
	return caCertPool, nil
}
