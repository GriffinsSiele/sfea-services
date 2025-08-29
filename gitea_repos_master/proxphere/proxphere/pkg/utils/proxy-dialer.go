package utils

import (
	"context"
	"fmt"
	"net"
	"net/url"

	"golang.org/x/net/proxy"
)

type ProxyDialer struct {
	proxyURL *url.URL
}

func NewProxyDialer(proxyURL *url.URL) *ProxyDialer {
	return &ProxyDialer{
		proxyURL: proxyURL,
	}
}

func (t *ProxyDialer) DialContext(ctx context.Context, network, addr string) (net.Conn, error) {
	dialer, err := proxy.FromURL(t.proxyURL, proxy.Direct)
	if err != nil {
		return nil, fmt.Errorf("failed to create proxy dialer: %w", err)
	}
	conn, err := dialer.Dial(network, addr)
	if err != nil {
		return nil, fmt.Errorf("failed to dial proxy: %w", err)
	}
	return conn, nil
}
