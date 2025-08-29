package firewall

import (
	"context"
	"fmt"
	"log/slog"
	"net"
	"os"
)

type Firewall struct {
	handler *Handler
}

func NewFirewall(handler *Handler) *Firewall {
	return &Firewall{
		handler: handler,
	}
}

func (f *Firewall) Listen(ctx context.Context) error {
	ln, err := net.Listen("tcp", os.Getenv("TCP_FIREWALL_LISTEN_ADDR"))
	if err != nil {
		return fmt.Errorf("failed to listen on %s: %w", os.Getenv("TCP_FIREWALL_LISTEN_ADDR"), err)
	}
	//goland:noinspection GoUnhandledErrorResult
	defer ln.Close()

	slog.InfoContext(ctx, "firewall started", "address", ln.Addr().String())

	go func() {
		for {
			conn, err := ln.Accept()
			if err != nil {
				slog.ErrorContext(ctx, "failed to accept connection", "error", err)
				continue
			}
			go f.handler.Handle(ctx, conn)
		}
	}()

	<-ctx.Done()
	return context.Cause(ctx)
}
