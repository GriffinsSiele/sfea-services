package tcp

import (
	"bytes"
	"context"
	"crypto/tls"
	"crypto/x509"
	"errors"
	"fmt"
	"html/template"
	"io"
	"net"
	"net/http"
	"net/http/httputil"
	"os"
	"strconv"
	"sync"
	"syscall"

	"github.com/sirupsen/logrus"
	"go.opentelemetry.io/otel/trace"
)

type Server struct {
	handler *Handler
	storage *sync.Map
	tracer  trace.Tracer
}

func NewServer(handler *Handler, storage *sync.Map, tracer trace.Tracer) *Server {
	return &Server{
		handler: handler,
		storage: storage,
		tracer:  tracer,
	}
}

func (t *Server) ListenAndServeTCP(host string, port uint) error {
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

	done := make(chan struct{}, 1)

	go func() {
		for {
			conn, err := listener.Accept()
			if err != nil {
				logrus.WithError(err).Error("failed to accept")
				continue
			}

			ctx := context.Background()
			if parentCtx, ok := t.storage.Load(syscall.Getpid()); ok {
				ctx = parentCtx.(context.Context)
			}

			ctx, span := t.tracer.Start(ctx, "accept TCP conn")

			logrus.WithContext(ctx).WithFields(logrus.Fields{
				"local.addr":  conn.LocalAddr().String(),
				"remote.addr": conn.RemoteAddr().String(),
			}).Debug("accepted TCP conn")

			go func(conn net.Conn) {
				defer func() {
					_ = conn.Close()
					logrus.WithContext(ctx).Debug("closed TCP conn")
					span.End()
				}()

				if eh := t.handler.HandleWithProto(ctx, conn, "http"); eh != nil {
					logrus.WithContext(ctx).WithError(eh).Error("failed to handle")

					if ew := t.writeErrorResponse(ctx, conn, eh); ew != nil {
						logrus.WithContext(ctx).WithError(ew).Error("failed to write error response")
					}
				}

				logrus.WithContext(ctx).Debug("handled TCP conn")
			}(conn)
		}
	}()

	<-done

	return nil
}

func (t *Server) ListenAndServeTLS(host string, port uint) error {
	certificate, err := tls.LoadX509KeyPair(os.Getenv("TLS_CERT"), os.Getenv("TLS_PRIVATE_KEY"))
	if err != nil {
		return fmt.Errorf("failed to load certificate: %w", err)
	}
	caCertPool, err := t.loadCACertPool()
	if err != nil {
		return fmt.Errorf("failed to load ca cert pool: %w", err)
	}
	config := &tls.Config{
		Certificates: []tls.Certificate{
			certificate,
		},
		ClientCAs: caCertPool,
	}
	listener, err := tls.Listen("tcp", net.JoinHostPort(host, strconv.Itoa(int(port))), config)
	if err != nil {
		return fmt.Errorf("failed to listen: %w", err)
	}
	//goland:noinspection GoUnhandledErrorResult
	defer listener.Close()

	logrus.WithFields(logrus.Fields{
		"host": host,
		"port": port,
	}).Info("listen and serve TLS server")

	done := make(chan struct{}, 1)

	go func() {
		for {
			conn, err := listener.Accept()
			if err != nil {
				logrus.WithError(err).Error("failed to accept")
				continue
			}

			ctx := context.Background()
			if parentCtx, ok := t.storage.Load(syscall.Getpid()); ok {
				ctx = parentCtx.(context.Context)
			}

			ctx, span := t.tracer.Start(ctx, "accept TLS conn")

			logrus.WithContext(ctx).WithFields(logrus.Fields{
				"local.addr":  conn.LocalAddr().String(),
				"remote.addr": conn.RemoteAddr().String(),
			}).Debug("accepted TLS conn")

			go func(conn net.Conn) {
				defer func() {
					_ = conn.Close()
					logrus.WithContext(ctx).Debug("closed TLS conn")
					span.End()
				}()

				if eh := t.handler.HandleWithProto(ctx, conn, "https"); eh != nil {
					logrus.WithContext(ctx).WithError(eh).Error("failed to handle")

					if ew := t.writeErrorResponse(ctx, conn, eh); ew != nil {
						logrus.WithContext(ctx).WithError(ew).Error("failed to write error response")
					}
				}

				logrus.WithContext(ctx).Debug("handled TLS conn")
			}(conn)
		}
	}()

	<-done
	return nil
}

func (t *Server) loadCACertPool() (*x509.CertPool, error) {
	caCertPool := x509.NewCertPool()
	caCertPath := os.Getenv("TLS_CA_CERT")
	caCert, err := os.ReadFile(caCertPath)
	if err != nil {
		return nil, fmt.Errorf("failed to read ca cert: %w", err)
	}
	if ok := caCertPool.AppendCertsFromPEM(caCert); !ok {
		return nil, errors.New("failed to append ca cert")
	}
	return caCertPool, nil
}

func (t *Server) writeErrorResponse(_ context.Context, conn net.Conn, errToResp error) error {
	// language=gotemplate
	message := `<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8"/>
    <title>Proxy Service Error: {{ .Error }}</title>
</head>
<body>
<h1>Proxy Service Error</h1>
<p>{{ .Error }}</p>
</body>
</html>`
	tmpl, err := template.New("error").Parse(message)
	if err != nil {
		return fmt.Errorf("failed to parse error template: %w", err)
	}
	buf := bytes.NewBuffer([]byte{})
	if err = tmpl.Execute(buf, map[string]any{"Error": errToResp}); err != nil {
		return fmt.Errorf("failed to execute error template: %w", err)
	}
	resp := &http.Response{
		ProtoMajor: 1, ProtoMinor: 1,
		StatusCode: http.StatusInternalServerError,
		Header:     http.Header{"Content-Type": []string{"text/html; charset=utf-8"}},
		Body:       io.NopCloser(buf),
	}
	respBytes, err := httputil.DumpResponse(resp, true)
	if err != nil {
		return fmt.Errorf("failed to dump response: %w", err)
	}
	if _, err := conn.Write(respBytes); err != nil {
		return fmt.Errorf("failed to write response: %w", err)
	}
	return nil
}
