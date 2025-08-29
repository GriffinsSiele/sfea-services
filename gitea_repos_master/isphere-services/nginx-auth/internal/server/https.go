package server

import (
	"context"
	"crypto/tls"
	"fmt"
	"log/slog"
	"net"
	"net/http"

	"golang.org/x/crypto/acme/autocert"
	"i-sphere.ru/nginx-auth/internal/configuration"
	"i-sphere.ru/nginx-auth/internal/contract"
	"i-sphere.ru/nginx-auth/internal/handler"
	"i-sphere.ru/nginx-auth/internal/tcp"
)

type HTTPS struct {
	handler *handler.HTTP
	params  *configuration.Params
}

func NewHTTPS(h *handler.HTTP, p *configuration.Params) *HTTPS {
	return &HTTPS{
		handler: h,
		params:  p,
	}
}

func (s *HTTPS) ListenAndServe(ctx context.Context) error {
	listener, err := net.Listen("tcp", s.params.HTTPS.Addr())
	if err != nil {
		return fmt.Errorf("failed to net listen: %w", err)
	}
	//goland:noinspection GoUnhandledErrorResult
	defer listener.Close()

	var config *tls.Config

	if s.params.Local {
		// Local mode with self-signed certificate
		certificate, err := tls.LoadX509KeyPair(s.params.HTTPS.CertFile, s.params.HTTPS.KeyFile)
		if err != nil {
			return fmt.Errorf("failed to load x509 keypair: %w", err)
		}

		config = &tls.Config{
			Certificates: []tls.Certificate{certificate},
		}
	} else {
		// Production mode with LetsEncrypt certificate
		whitelistHostsMap := map[string]bool{}
		for _, upstream := range s.params.Upstreams {
			for _, route := range upstream.Routes {
				whitelistHostsMap[route.Server.Host] = true
			}
		}

		whitelistHosts := make([]string, 0, len(whitelistHostsMap))
		for whitelistHost := range whitelistHostsMap {
			whitelistHosts = append(whitelistHosts, whitelistHost)
		}

		certbot := &autocert.Manager{
			Prompt:     autocert.AcceptTOS,
			HostPolicy: autocert.HostWhitelist(whitelistHosts...),
			Cache:      autocert.DirCache(s.params.HTTPS.LetsEncryptCacheDir),
		}

		config = certbot.TLSConfig()
	}

	httpServer := http.Server{
		Addr:        s.params.HTTPS.Addr(),
		Handler:     s.handler,
		TLSConfig:   config,
		ConnContext: connContext,
	}

	slog.With("https.host", s.params.HTTPS.Host, "https.port", s.params.HTTPS.Port).InfoContext(ctx, "starting HTTPS server")

	buffTLSListener := tcp.NewBufferedTLSListener(listener, config)
	if err := httpServer.Serve(buffTLSListener); err != nil {
		return fmt.Errorf("failed to serve: %w", err)
	}

	if err := httpServer.Shutdown(ctx); err != nil {
		return fmt.Errorf("failed to shutdown: %w", err)
	}

	return nil
}

func connContext(ctx context.Context, conn net.Conn) context.Context {
	tlsConn, ok := conn.(*tls.Conn)
	if !ok {
		return ctx
	}

	buffConn, ok := tlsConn.NetConn().(*tcp.BufferedConn)
	if !ok {
		return ctx
	}

	return context.WithValue(ctx, contract.ConnContextKey, buffConn)
}
