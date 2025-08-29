package socks5

import (
	"context"
	"fmt"
	"net"
	"strconv"

	"github.com/sirupsen/logrus"
	"go.opentelemetry.io/otel/trace"
)

type Server struct {
	handler *Handler
	tracer  trace.Tracer
}

func NewServer(handler *Handler, tracer trace.Tracer) *Server {
	return &Server{
		handler: handler,
		tracer:  tracer,
	}
}

func (t *Server) ListenAndServe(host string, port uint) error {
	listener, err := net.Listen("tcp", net.JoinHostPort(host, strconv.Itoa(int(port))))
	if err != nil {
		return fmt.Errorf("failed to listen: %w", err)
	}
	//goland:noinspection GoUnhandledErrorResult
	defer listener.Close()

	logrus.WithFields(logrus.Fields{
		"host": host,
		"port": port,
	}).Info("listen and serve TCP server")

	done := make(chan bool, 1)

	go func() {
		for {
			ctx, span := t.tracer.Start(context.Background(), "accept SOCKS5 conn")

			conn, err := listener.Accept()
			if err != nil {
				logrus.WithContext(ctx).WithError(err).Error("failed to accept")
				span.End()
				continue
			}

			logrus.WithContext(ctx).WithFields(logrus.Fields{
				"local.addr":  conn.LocalAddr().String(),
				"remote.addr": conn.RemoteAddr().String(),
			}).Debug("accepted SOCKS5 conn")

			go func(conn net.Conn) {
				defer func() {
					_ = conn.Close()
					logrus.WithContext(ctx).Debug("closed SOCKS5 conn")
					span.End()
				}()

				if err := t.handler.Handle(ctx, conn); err != nil {
					logrus.WithContext(ctx).WithError(err).Error("failed to handle")
				}

				logrus.WithContext(ctx).Debug("handled SOCKS5 conn")
			}(conn)
		}
	}()

	<-done

	return nil
}
