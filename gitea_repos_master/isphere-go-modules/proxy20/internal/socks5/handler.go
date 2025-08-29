package socks5

import (
	"bytes"
	"context"
	"fmt"
	"io"
	"net"
	"slices"
	"sync"
	"syscall"

	"github.com/sirupsen/logrus"
	"go.opentelemetry.io/otel/trace"

	"i-sphere.ru/proxy/internal/model/socks5"
	"i-sphere.ru/proxy/internal/repository"
)

type Handler struct {
	proxyRepo *repository.Proxy
	storage   *sync.Map
	tracer    trace.Tracer
}

func NewHandler(proxyRepo *repository.Proxy, storage *sync.Map, tracer trace.Tracer) *Handler {
	return &Handler{
		proxyRepo: proxyRepo,
		storage:   storage,
		tracer:    tracer,
	}
}

func (t *Handler) Handle(ctx context.Context, conn net.Conn) error {
	ctx, span := t.tracer.Start(ctx, "handle SOCKS5 conn")
	defer span.End()

	if err := t.Connect(ctx, conn); err != nil {
		logrus.WithContext(ctx).WithError(err).Error("failed to connect")
		return fmt.Errorf("failed to decorate socket: %w", err)
	}

	logrus.WithContext(ctx).Debug("SOCK5 connected")

	if err := t.Proxy(ctx, conn); err != nil {
		logrus.WithContext(ctx).WithError(err).Errorf("failed to proxy socket")
		return fmt.Errorf("failed to proxy socket: %w", err)
	}

	logrus.WithContext(ctx).Debug("SOCK5 proxy completed")
	return nil
}

func (t *Handler) Connect(ctx context.Context, conn net.Conn) error {
	ctx, span := t.tracer.Start(ctx, "connect SOCKS5 conn")
	defer span.End()

	// ClientHello
	var clientHello socks5.ClientHello
	if err := clientHello.UnmarshalReader(conn); err != nil {
		logrus.WithContext(ctx).WithError(err).Error("failed to unmarshal client hello")
		return fmt.Errorf("failed to unmarshal client hello: %w", err)
	}
	if clientHello.Version != 0x05 {
		err := fmt.Errorf("unsupported version: %d", clientHello.Version)
		logrus.WithContext(ctx).WithError(err).Error("unsupported version")
		return err
	}
	if !slices.Contains(clientHello.AuthenticationMethods, 0x00) {
		err := fmt.Errorf("no authentication method")
		logrus.WithContext(ctx).WithError(err).Error("no authentication method")
		return err
	}

	logrus.WithContext(ctx).WithFields(logrus.Fields{
		"version":     0x05,
		"auth.method": 0x00,
	}).Debug("client hello")

	// ServerHello
	serverHello := socks5.ServerHello{
		Version:              0x05,
		AuthenticationMethod: 0x00,
	}
	serverHelloBytes, err := serverHello.MarshalBytes()
	if err != nil {
		logrus.WithContext(ctx).WithError(err).Error("failed to marshal server hello")
		return fmt.Errorf("failed to marshal server hello: %w", err)
	}
	if _, err := conn.Write(serverHelloBytes); err != nil {
		logrus.WithContext(ctx).WithError(err).Error("failed to write server hello")
		return fmt.Errorf("failed to write server hello: %w", err)
	}

	logrus.WithContext(ctx).Debug("server hello sent")

	// ClientRequest
	var clientRequest socks5.ClientRequest
	if err := clientRequest.UnmarshalReader(conn); err != nil {
		logrus.WithContext(ctx).WithError(err).Error("failed to unmarshal client request")
		return fmt.Errorf("failed to unmarshal client request: %w", err)
	}
	if clientRequest.Version != 0x05 {
		logrus.WithContext(ctx).WithError(err).Error("unsupported version")
		return fmt.Errorf("unsupported version: %d", clientRequest.Version)
	}
	if clientRequest.Command != 0x01 {
		logrus.WithContext(ctx).WithError(err).Error("unsupported command")
		return fmt.Errorf("unsupported command: %d", clientRequest.Command)
	}

	logrus.WithContext(ctx).WithFields(logrus.Fields{
		"version": 0x05,
		"command": 0x01,
	}).Debug("client request")

	// ServerResponse
	serverResponse := socks5.ServerResponse{
		Version: 0x05,
		Status:  0x00,
	}
	serverResponseBytes, err := serverResponse.MarshalBytes()
	if err != nil {
		logrus.WithContext(ctx).WithError(err).Error("failed to marshal server response")
		return fmt.Errorf("failed to marshal server response: %w", err)
	}
	if _, err := conn.Write(serverResponseBytes); err != nil {
		logrus.WithContext(ctx).WithError(err).Error("failed to write server response")
		return fmt.Errorf("failed to write server response: %w", err)
	}

	logrus.WithContext(ctx).Debug("server response sent")
	return nil
}

type handledProtocol string

const (
	handledProtocolTCP handledProtocol = "TCP"
	handledProtocolTLS handledProtocol = "TLS"
)

func (t *Handler) Proxy(ctx context.Context, conn net.Conn) error {
	ctx, span := t.tracer.Start(ctx, "proxy SOCKS5 conn")
	defer span.End()

	t.storage.Store(syscall.Getpid(), ctx)
	defer func() {
		t.storage.Delete(syscall.Getpid())
	}()

	var buf [3]byte
	if _, err := conn.Read(buf[:]); err != nil {
		logrus.WithContext(ctx).WithError(err).Error("failed to read initial data")
		return fmt.Errorf("failed to read initial data: %w", err)
	}

	proto := handledProtocolTCP
	if bytes.Equal(buf[1:3], []byte{0x03, 0x01}) {
		proto = handledProtocolTLS
	}

	var dstAddr string
	if proto == handledProtocolTCP {
		dstAddr = "127.0.0.1:8082"
	} else {
		dstAddr = "127.0.0.1:8080"
	}

	logrus.WithContext(ctx).WithField("protocol", proto).Debug("request protocol detected")

	dst, err := (&net.Dialer{}).DialContext(ctx, "tcp", dstAddr)
	if err != nil {
		logrus.WithContext(ctx).WithError(err).WithField("destination.addr", dstAddr).Error("failed to dial internal server")
		return fmt.Errorf("failed to dial internal server: %s: %w", dstAddr, err)
	}

	logrus.WithContext(ctx).WithField("destination.addr", dstAddr).Debug("internal server connected")

	defer func() {
		if err := dst.Close(); err != nil {
			logrus.WithContext(ctx).WithError(err).Error("failed to close internal server")
		}
		logrus.WithContext(ctx).Debug("internal server disconnected")
	}()

	var readBytesCount, writeBytesCount int64

	go func() {
		n, err := io.Copy(conn, dst)
		if err != nil {
			logrus.WithContext(ctx).WithError(err).Error("failed to copy data from internal server to socket")
			return
		}

		readBytesCount += n
	}()

	n1, err := dst.Write(buf[:])
	if err != nil {
		logrus.WithContext(ctx).WithError(err).Error("failed to write initial data")
		return fmt.Errorf("failed to write initial data: %w", err)
	}

	writeBytesCount += int64(n1)

	n2, err := io.Copy(dst, conn)
	if err != nil {
		logrus.WithContext(ctx).WithError(err).Error("failed to copy data from socket to internal server")
		return fmt.Errorf("failed to copy data from socket to internal server: %w", err)
	}

	writeBytesCount += n2

	logrus.WithContext(ctx).WithFields(logrus.Fields{
		"write": writeBytesCount,
		"read":  readBytesCount,
	}).Debug("copied between socket and internal server")
	return nil
}
