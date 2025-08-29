package proxy

import (
	"bufio"
	"bytes"
	"context"
	"crypto/tls"
	"fmt"
	"io"
	"log/slog"
	"net"
	"net/http"
	"net/http/httputil"
	"os"
	"time"

	"i-sphere.ru/proxphere/tor-proxy/internal/tor"
)

type Handler struct {
	pool *tor.Pool
}

func NewHandler(pool *tor.Pool) *Handler {
	return &Handler{
		pool: pool,
	}
}

func (h *Handler) HandleConn(ctx context.Context, conn net.Conn) error {
	req, err := h.handshake(ctx, conn)
	if err != nil {
		return fmt.Errorf("failed to handshake: %w", err)
	}

	if req != nil {
		if err = h.transfer(ctx, conn, req); err != nil {
			return fmt.Errorf("failed to transfer: %w", err)
		}
	} // else nothing to do

	return nil
}

func (h *Handler) handshake(ctx context.Context, conn net.Conn) (*http.Request, error) {
	reader := bufio.NewReader(conn)

	req, err := http.ReadRequest(reader)
	if err != nil {
		return nil, fmt.Errorf("failed to read connect request: %w", err)
	}

	resp := &http.Response{
		ProtoMajor: req.ProtoMajor,
		ProtoMinor: req.ProtoMinor,
		StatusCode: http.StatusOK,
	}

	if req.Method == http.MethodGet && req.RequestURI == "/health" {
		req = nil // reset the request
		if err = h.health(ctx); err != nil {
			resp.StatusCode = http.StatusInternalServerError
			resp.Body = io.NopCloser(bytes.NewReader([]byte(err.Error())))
		} else {
			resp.Body = io.NopCloser(bytes.NewReader([]byte("OK")))
		}
	} else if req.Method != http.MethodConnect {
		return nil, fmt.Errorf("unexpected method on connect request: %s", req.Method)
	}

	if err = resp.Write(conn); err != nil {
		return nil, fmt.Errorf("failed to write connect response: %w", err)
	}

	return req, nil
}

func (h *Handler) health(ctx context.Context) error {
	acquireTimeStart := time.Now()

	remoteConn, err := h.pool.Acquire(ctx, "tcp", "i-sphere.ru:443")
	if err != nil {
		return fmt.Errorf("failed to acquire tor test forwarding: %w", err)
	}
	//goland:noinspection GoUnhandledErrorResult
	defer remoteConn.Close()

	acquireTime := time.Since(acquireTimeStart)

	req, err := http.NewRequest(http.MethodGet, "https://i-sphere.ru/2.00/my-ip.php", http.NoBody)
	if err != nil {
		return fmt.Errorf("failed to create health request: %w", err)
	}
	req.Header.Set("User-Agent", fmt.Sprintf("%s/%s (linux; x64)", os.Getenv("NODE_NAME"), os.Getenv("POD_NAME")))

	healthBytes, err := httputil.DumpRequest(req, true)
	if err != nil {
		return fmt.Errorf("failed to dump health request: %w", err)
	}

	tlsConn := tls.Client(remoteConn, &tls.Config{
		InsecureSkipVerify: true,
	})
	//goland:noinspection GoUnhandledErrorResult
	defer tlsConn.Close()

	healthTimeStart := time.Now()

	if _, err = tlsConn.Write(healthBytes); err != nil {
		return fmt.Errorf("failed to write health request: %w", err)
	}

	var buf [1024]byte
	n, err := tlsConn.Read(buf[:])
	if err != nil {
		return fmt.Errorf("failed to read health response: %w", err)
	}

	resp, err := http.ReadResponse(bufio.NewReader(bytes.NewReader(buf[:n])), req)
	if err != nil {
		return fmt.Errorf("cannot read response: %w", err)
	}
	//goland:noinspection GoUnhandledErrorResult
	defer resp.Body.Close()

	if resp.StatusCode != http.StatusOK {
		return fmt.Errorf("unexpected status code: %d", resp.StatusCode)
	}

	respBytes, err := io.ReadAll(resp.Body)
	if err != nil {
		return fmt.Errorf("failed to read response body: %w", err)
	}

	healthTime := time.Since(healthTimeStart)

	slog.InfoContext(ctx, "health ok",
		"ip", string(bytes.TrimSpace(respBytes)),
		"acquire time", acquireTime,
		"health time", healthTime,
	)

	return nil
}

func (h *Handler) transfer(ctx context.Context, rw io.ReadWriter, req *http.Request) error {
	remoteConn, err := h.pool.Acquire(ctx, "tcp", req.Host)
	if err != nil {
		return fmt.Errorf("failed to acquire tor forwarding: %w", err)
	}
	//goland:noinspection GoUnhandledErrorResult
	defer remoteConn.Close()

	errCh := make(chan error, 1)

	go h.pipe(rw, remoteConn, errCh)
	go h.pipe(remoteConn, rw, errCh)

	select {
	case err = <-errCh:
		if err != nil {
			return fmt.Errorf("failed to pipe: %w", err)
		}
	case <-ctx.Done():
		return context.Cause(ctx)
	}

	return nil
}

func (h *Handler) pipe(dst io.Writer, src io.Reader, errCh chan error) {
	_, err := io.Copy(dst, src)
	errCh <- err
}
