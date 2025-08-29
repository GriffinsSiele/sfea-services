package socks5

import (
	"context"
	"errors"
	"fmt"
	"log/slog"
	"net"
)

type Server struct {
	handler *Handler
}

func NewServer(h *Handler) *Server {
	return &Server{
		handler: h,
	}
}

func (s *Server) Start(ctx context.Context) error {
	listenConfig := new(net.ListenConfig)
	listener, err := listenConfig.Listen(ctx, "tcp", ":1080")
	if err != nil {
		return fmt.Errorf("failed to listen: %w", err)
	}

	go func() {
		<-ctx.Done()
		_ = listener.Close()
	}()

	slog.InfoContext(ctx, "listening on", "address", listener.Addr())

	for {
		conn, err := listener.Accept()
		if err != nil {
			slog.ErrorContext(ctx, "failed to accept connection", "error", err)
			if errors.Is(err, net.ErrClosed) {
				return fmt.Errorf("network closed: %w", err)
			}
			continue
		}

		go s.handleConnection(ctx, conn)
	}
}

func (s *Server) handleConnection(ctx context.Context, conn net.Conn) {
	if err := s.handler.Handle(ctx, conn); err != nil {
		slog.ErrorContext(ctx, "failed to handle connection", "error", err)
	}
}
