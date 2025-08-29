package handlers

import (
	"context"
	"crypto/tls"
	"encoding/binary"
	"errors"
	"fmt"
	"io"
	"net"
	"os"
	"slices"
	"time"

	"github.com/charmbracelet/log"
	"github.com/go-redis/redis/v8"
	"go.i-sphere.ru/proxy/pkg/models/socks5"
	"go.i-sphere.ru/proxy/pkg/trackers"
	"go.i-sphere.ru/proxy/pkg/utils"
)

type SOCKS5 struct {
	redis *redis.Client

	log *log.Logger
}

func NewSOCKS5(redis *redis.Client) *SOCKS5 {
	return &SOCKS5{
		redis: redis,

		log: log.WithPrefix("handlers.SOCKS5"),
	}
}

func (s *SOCKS5) Handle(ctx context.Context, lConn net.Conn) {
	tracer, closer := trackers.MustTracerCloser()
	defer trackers.MustClose(closer)

	ctx, span := trackers.StartSpanWithContext(ctx, tracer, "handle socks5 request")
	defer span.Finish()

	span.SetTag("scope", "socks5")

	ctx, cancel := context.WithTimeout(ctx, 5*time.Minute)
	defer cancel()

	lLog := s.log.With("local_addr", lConn.LocalAddr(), "remote_addr", lConn.RemoteAddr())
	lLog.Debug("connection opened")

	defer func() {
		//goland:noinspection GoUnhandledErrorResult
		lConn.Close()
		lLog.Debug("connection closed")
	}()

	var clientHello socks5.ClientHello
	if err := socks5.UnmarshalClientHello(lConn, &clientHello); err != nil {
		lLog.With("error", trackers.Fail(span, err)).Error("failed to unmarshal client hello")
		return
	}

	var username, password string

	switch {
	case slices.Contains(clientHello.AuthenticationMethods, socks5.UsernamePassword):
		if err := socks5.MarshalServerHello(lConn, socks5.NewServerHello(socks5.UsernamePassword)); err != nil {
			lLog.With("error", trackers.Fail(span, err)).Error("failed to marshal server hello with username/password")
			return
		}

		var clientNegotiation socks5.ClientNegotiation
		if err := socks5.UnmarshalClientNegotiation(lConn, &clientNegotiation); err != nil {
			lLog.With("error", trackers.Fail(span, err)).Error("failed to unmarshal client negotiation")
			return
		}

		username, password = clientNegotiation.Username, clientNegotiation.Password

		if err := socks5.MarshalServerNegotiation(lConn, socks5.NewServerNegotiation(socks5.NegotiationSuccess)); err != nil {
			lLog.With("error", trackers.Fail(span, err)).Error("failed to marshal server negotiation")
			return
		}

	case slices.Contains(clientHello.AuthenticationMethods, socks5.NoAuthentication):
		if err := socks5.MarshalServerHello(lConn, socks5.NewServerHello(socks5.NoAuthentication)); err != nil {
			lLog.With("error", trackers.Fail(span, err)).Error("failed to marshal server hello with no authentication")
			return
		}

	default:
		if err := socks5.MarshalServerHello(lConn, socks5.NewServerHello(socks5.NoAcceptable)); err != nil {
			lLog.With("error", trackers.Fail(span, err)).Error("failed to marshal server hello with no authentication")
			return
		}
	}

	var request socks5.Request
	if err := socks5.UnmarshalRequest(lConn, &request); err != nil {
		if hErr := s.handleUnmarshalRequestError(lConn, trackers.Fail(span, err)); err != nil {
			lLog.With("error", err, "unmarshal_error", trackers.Fail(span, hErr)).Error("failed to unmarshal request")
			return
		}
	}

	if err := socks5.MarshalResponse(lConn, socks5.NewResponse(socks5.RequestGranted)); err != nil {
		lLog.With("error", trackers.Fail(span, err)).Error("failed to marshal response")
		return
	}

	headerBytes := make([]byte, 3)
	if n, err := lConn.Read(headerBytes); err != nil || n != len(headerBytes) {
		lLog.With("error", trackers.Fail(span, err)).Error("failed to read header")
		return
	}

	rAddr := s.getRemoteAddr(headerBytes)

	rConn, err := s.connectToDestination(ctx, rAddr)
	if err != nil {
		lLog.With("error", trackers.Fail(span, err)).Error("failed to connect to destination")
		return
	}

	rLog := s.log.With("local_addr", rConn.LocalAddr(), "remote_addr", rConn.RemoteAddr())
	rLog.Debug("destination opened")

	defer func() {
		//goland:noinspection GoUnhandledErrorResult
		rConn.Close()
		rLog.Debug("destination closed")
	}()

	if username != "" || password != "" {
		if err = s.redis.HSet(ctx, "proxphere", rConn.LocalAddr().String(), username+":"+password).Err(); err != nil {
			lLog.With("error", trackers.Fail(span, err)).Error("failed to store destination in redis")
			return
		} else {
			lLog.Debug("stored destination in redis", "key", rConn.LocalAddr().String())
		}

		defer func(rConn net.Conn, lLog *log.Logger) {
			s.redis.HDel(ctx, "proxphere", rConn.LocalAddr().String())
			lLog.Debug("removed destination from redis", "key", rConn.LocalAddr().String())
		}(rConn, lLog)
	}

	go func() {
		n, rErr := io.Copy(lConn, rConn)
		if rErr != nil {
			if !errors.Is(rErr, net.ErrClosed) {
				rLog.With("error", trackers.Fail(span, rErr)).Error("failed to copy data from destination to local connection")
			}
		}
		//goland:noinspection GoUnhandledErrorResult
		lConn.Close()
		span.LogKV("bytes copied from remote server to client", n)
	}()

	n1, err := rConn.Write(headerBytes)
	if err != nil {
		if !errors.Is(err, net.ErrClosed) {
			lLog.With("error", trackers.Fail(span, err)).Error("failed to write header to destination")
		}
		//goland:noinspection GoUnhandledErrorResult
		rConn.Close()
		return
	}

	n2, err := io.Copy(rConn, lConn)
	if err != nil {
		if !errors.Is(err, net.ErrClosed) {
			lLog.With("error", trackers.Fail(span, err)).Error("failed to copy data from local connection to destination")
		}
		//goland:noinspection GoUnhandledErrorResult
		rConn.Close()
	}
	span.LogKV("bytes copied from client to remote server", int64(n1)+n2)
}

func (s *SOCKS5) handleUnmarshalRequestError(lConn net.Conn, err error) error {
	switch {
	case errors.Is(err, socks5.CommandNotSupportedErr):
		if err := socks5.MarshalResponse(lConn, socks5.NewResponse(socks5.CommandNotSupported)); err != nil {
			return fmt.Errorf("failed to marshal response: %w", err)
		}
		return nil
	case errors.Is(err, socks5.AddressTypeNotSupportedErr):
		if err := socks5.MarshalResponse(lConn, socks5.NewResponse(socks5.AddressTypeNotSupported)); err != nil {
			return fmt.Errorf("failed to marshal response: %w", err)
		}
		return nil
	default:
		if err := socks5.MarshalResponse(lConn, socks5.NewResponse(socks5.GeneralFailure)); err != nil {
			return fmt.Errorf("failed to marshal response: %w", err)
		}
		return nil
	}
}

func (s *SOCKS5) getRemoteAddr(headerBytes []byte) string {
	if utils.IfB(binary.BigEndian.Uint16(headerBytes[1:3]) == tls.VersionTLS10) {
		return net.JoinHostPort(os.Getenv("BACKWARD_TLS_SERVER_HOST"), os.Getenv("TLS_SERVER_PORT"))
	}
	return net.JoinHostPort(os.Getenv("BACKWARD_TCP_SERVER_HOST"), os.Getenv("TCP_SERVER_PORT"))
}

func (s *SOCKS5) connectToDestination(ctx context.Context, rAddr string) (net.Conn, error) {
	conn, err := new(net.Dialer).DialContext(ctx, "tcp", rAddr)
	if err != nil {
		return nil, fmt.Errorf("failed to connect to destination: %w", err)
	}
	return conn, nil
}
