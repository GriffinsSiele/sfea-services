package tcp

import (
	"context"
	"encoding/base64"
	"fmt"
	"net"
	"net/http"
	"net/http/httputil"
	"net/url"
	"strconv"
	"strings"
	"time"

	"github.com/sirupsen/logrus"
	"go.opentelemetry.io/otel/trace"
	"golang.org/x/net/proxy"
)

type HTTPDialer struct {
	addr     string
	username string
	password string
	target   *url.URL
	forward  proxy.Dialer
	tracer   trace.Tracer
	callback func(context.Context) error
}

func NewHTTPDialer(uri *url.URL, forward proxy.Dialer, target *url.URL, tracer trace.Tracer, callback func(context.Context) error) (proxy.ContextDialer, error) {
	t := &HTTPDialer{
		addr:     uri.Host,
		forward:  forward,
		target:   target,
		tracer:   tracer,
		callback: callback,
	}
	if uri.User != nil {
		t.username = uri.User.Username()
		t.password, _ = uri.User.Password()
	}
	return t, nil
}

func (t *HTTPDialer) DialContext(ctx context.Context, network, addr string) (net.Conn, error) {
	start := time.Now()

	ctx, span := t.tracer.Start(ctx, "cascade proxy dial")
	defer span.End()

	conn, err := t.forward.Dial(network, t.addr)
	if err != nil {
		logrus.WithContext(ctx).WithError(err).Error("failed to dial")
		return nil, fmt.Errorf("failed to dial: %w", err)
	}

	errorAndClose := func(err error) error {
		_ = conn.Close()
		logrus.WithContext(ctx).WithError(err).Error(err.Error())
		return err
	}

	logrus.WithContext(ctx).WithFields(logrus.Fields{"network": network, "addr": t.addr}).Debug("forward conn dialed")

	hostname, port, ok := strings.Cut(addr, ":")
	if !ok {
		return nil, errorAndClose(fmt.Errorf("failed to parse addr: %w", err))
	}

	req := &http.Request{
		Method: http.MethodConnect,
		URL:    &url.URL{Scheme: t.target.Scheme, Host: net.JoinHostPort(t.target.Host, port)},
		Header: make(http.Header),
	}
	req.Header.Set("Host", hostname)
	req.Header.Set("Proxy-Authorization", "Basic "+base64.StdEncoding.EncodeToString([]byte(t.username+":"+t.password)))

	reqBytes, err := httputil.DumpRequestOut(req, true)
	if err != nil {
		return nil, errorAndClose(fmt.Errorf("failed to dump request out: %w", err))
	}

	if _, err := conn.Write(reqBytes); err != nil {
		return nil, errorAndClose(fmt.Errorf("failed to write: %w", err))
	}

	logrus.WithContext(ctx).WithField("request", string(reqBytes)).Debug("wrote request to conn forward")

	// Set the dial timeout and cancel dialing if it is exceed
	timeout := 5 * time.Second
	ctx, cancel := context.WithTimeout(ctx, timeout)
	go func() {
		time.Sleep(timeout)
		select {
		case <-ctx.Done():
		default:
			logrus.WithContext(ctx).WithField("timeout", timeout).Warnf("dial timeout exceed")
			_ = conn.Close()
		}
	}()

	var buf [256]byte
	n, err := conn.Read(buf[:])
	if err != nil {
		cancel()
		return nil, errorAndClose(fmt.Errorf("failed to read: %w", err))
	}
	cancel()

	logrus.WithContext(ctx).WithFields(logrus.Fields{
		"response": string(buf[:n]),
		"duration": time.Since(start),
	}).Debug("read response from conn forward")

	_, rest, ok := strings.Cut(string(buf[:n]), " ")
	if !ok {
		return nil, errorAndClose(fmt.Errorf("failed to parse status: %s", string(buf[:n])))
	}

	statusCodeStr, _, ok := strings.Cut(rest, " ")
	if !ok {
		return nil, errorAndClose(fmt.Errorf("failed to parse status code: %s", rest))
	}

	statusCode, err := strconv.Atoi(statusCodeStr)
	if err != nil {
		return nil, errorAndClose(fmt.Errorf("failed to cast status code as int: %w", err))
	}

	if statusCode != http.StatusOK {
		return nil, errorAndClose(fmt.Errorf("unexpected status code on connect: %s: %d", conn.RemoteAddr(), statusCode))
	}

	if err = t.callback(ctx); err != nil {
		return nil, errorAndClose(fmt.Errorf("callback failed: %w", err))
	}

	logrus.WithContext(ctx).Debug("proxy conn dialed")

	return conn, nil
}
