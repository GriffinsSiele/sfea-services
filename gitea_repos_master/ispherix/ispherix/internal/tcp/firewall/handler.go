package firewall

import (
	"context"
	"errors"
	"fmt"
	"log/slog"
	"net"
	"os"
	"strings"
	"time"

	"go.i-sphere.ru/ispherix/internal/clickhouse"
	"go.i-sphere.ru/ispherix/internal/geoip"
	"go.i-sphere.ru/ispherix/internal/tcp/haproxy"
	"go.i-sphere.ru/ispherix/internal/tls/io"
	"go.i-sphere.ru/ispherix/pkg/tls"
	"go.i-sphere.ru/ispherix/pkg/tls/extension"
	"go.i-sphere.ru/ispherix/pkg/tls/frame"
	"go.i-sphere.ru/ispherix/pkg/tls/handshake"
	"golang.org/x/sync/errgroup"
)

type Handler struct {
	clickhousePool *clickhouse.Pool
	geoipDatabase  *geoip.Database
}

func NewHandler(clickhousePool *clickhouse.Pool, geoipDatabase *geoip.Database) *Handler {
	return &Handler{
		clickhousePool: clickhousePool,
		geoipDatabase:  geoipDatabase,
	}
}

func (h *Handler) Handle(ctx context.Context, clientConn net.Conn) {
	connStat := NewConnStat(slog.Default(), clientConn.RemoteAddr())
	connStat.DebugContext(ctx, "accepted client connection")

	defer func() {
		//goland:noinspection GoUnhandledErrorResult
		clientConn.Close()
		connStat.EndTime = time.Now()
		connStat.DebugContext(ctx, "closed client connection")
		go connStat.Flush(ctx, h.clickhousePool)
	}()

	country, err := h.geoipDatabase.FindCountryByAddr(ctx, clientConn.RemoteAddr())
	if err != nil {
		connStat.Error = err
		connStat.ErrorContext(ctx, "failed to find country")
		return
	}

	if country != nil {
		connStat.ClientCountryCode = country.ISOCode
	}

	serverConn, err := (&net.Dialer{}).DialContext(ctx, "tcp", os.Getenv("INTERNAL_SERVER_ADDR"))
	if err != nil {
		connStat.Error = err
		connStat.ErrorContext(ctx, "failed to dial server")
		return
	}

	connStat.ServerAddr = serverConn.LocalAddr()
	connStat.DebugContext(ctx, "accepted server connection")

	defer func() {
		//goland:noinspection GoUnhandledErrorResult
		serverConn.Close()
		connStat.DebugContext(ctx, "closed server connection")
	}()

	if err = haproxy.WriteHeader(clientConn, serverConn); err != nil {
		connStat.Error = err
		connStat.ErrorContext(ctx, "failed to write haproxy header")
		return
	}

	fn := func(f *tls.Frame) error {
		connStat.DebugContext(ctx, fmt.Sprintf(
			"%s %c %s", clientConn.RemoteAddr(), f.DirectionFor(clientConn), serverConn.RemoteAddr(),
		), "frame", f)

		if frameContent, ok := f.Content.(*frame.Handshake); ok {
			switch handshakeContent := frameContent.Content.(type) {
			case *handshake.ClientHello:
				connStat.ClientFingerprint = handshakeContent.JA3()

				for _, ext := range handshakeContent.Extensions {
					if extContent, ok := ext.Content.(*extension.ServerName); ok {
						var serverNames []string
						for _, entry := range extContent.Entries {
							serverNames = append(serverNames, entry.Name)
						}
						connStat.ServerName = strings.Join(serverNames, ",")
					}
				}
			case *handshake.ServerHello:
				connStat.ServerFingerprint = handshakeContent.JA3S()
			}
		}

		return nil
	}

	var wg errgroup.Group

	wg.Go(func() error {
		defer io.CloseRead(clientConn)
		defer io.CloseWrite(serverConn)
		if _, err := io.Copy(serverConn, clientConn, fn); err != nil && !errors.Is(err, net.ErrClosed) {
			return fmt.Errorf("failed to transfer data from client to server: %w", err)
		}
		return nil
	})

	wg.Go(func() error {
		defer io.CloseRead(serverConn)
		defer io.CloseWrite(clientConn)
		if _, err := io.Copy(clientConn, serverConn, fn); err != nil && !errors.Is(err, net.ErrClosed) {
			return fmt.Errorf("failed to transfer data from server to client: %w", err)
		}
		return nil
	})

	if err = wg.Wait(); err != nil {
		connStat.Error = err
		connStat.ErrorContext(ctx, "failed to transfer data")
		return
	}
}
