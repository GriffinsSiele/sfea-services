package proxy

import (
	"context"
	"errors"
	"log/slog"
	"net"
	"time"

	"go.uber.org/fx"
)

type Server struct {
	handler *Handler
	ln      net.Listener
}

func NewServer(handler *Handler, lc fx.Lifecycle, sd fx.Shutdowner) *Server {
	s := &Server{
		handler: handler,
	}

	cancelCtx, cancel := context.WithCancel(context.Background())

	lc.Append(fx.Hook{
		OnStart: func(_ context.Context) error {
			go func() {
				var err error
				if s.ln, err = net.Listen("tcp", ":8080"); err != nil {
					slog.ErrorContext(cancelCtx, "failed to listen and serve", "error", err)
					//goland:noinspection GoUnhandledErrorResult
					sd.Shutdown(fx.ExitCode(1))
				}
				s.listen(cancelCtx)
			}()
			return nil
		},

		OnStop: func(_ context.Context) error {
			defer cancel()
			if s.ln != nil {
				//goland:noinspection GoUnhandledErrorResult
				s.ln.Close()
			}
			return nil
		},
	})

	return s
}

func (s *Server) listen(ctx context.Context) {
	slog.InfoContext(ctx, "listening on", "address", s.ln.Addr())

	for {
		select {
		case <-ctx.Done():
			return
		default:
		}

		conn, err := s.ln.Accept()
		if err != nil {
			if errors.Is(err, net.ErrClosed) {
				return
			}
			slog.ErrorContext(ctx, "failed to accept connection", "error", err)
			continue
		}

		go func(conn net.Conn) {
			//goland:noinspection GoUnhandledErrorResult
			defer conn.Close()

			timeoutCtx, cancel := context.WithTimeout(ctx, 30*time.Second)
			defer cancel()

			if err := s.handler.HandleConn(timeoutCtx, conn); err != nil {
				slog.ErrorContext(ctx, "failed to handle connection", "error", err)
			}
		}(conn)
	}
}
