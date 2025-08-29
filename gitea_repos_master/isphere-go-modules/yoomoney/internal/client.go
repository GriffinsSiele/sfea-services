package internal

import (
	"context"
	"crypto/tls"
	"fmt"
	"net"
	"net/http"
	"net/url"
	"os"
	"time"

	"golang.org/x/net/proxy"
)

const (
	socks5Scheme = "socks5"
)

func NewClient() (*http.Client, error) {
	proxyURL, err := url.Parse(os.Getenv("PROXY_URL"))
	if err != nil {
		return nil, fmt.Errorf("failed to parse proxy url: %v", err)
	}

	if proxyURL.Scheme != socks5Scheme {
		return nil, fmt.Errorf("unsupported proxy scheme: %s", proxyURL.Scheme)
	}

	dialer, err := proxy.SOCKS5("tcp", proxyURL.Host, newAuthWithUser(proxyURL.User), proxy.Direct)
	if err != nil {
		return nil, fmt.Errorf("failed to create SOCKS5 dialer: %w", err)
	}

	transport := &http.Transport{
		DialContext: func(ctx context.Context, network, addr string) (net.Conn, error) {
			conn, err := dialer.Dial(network, addr)
			if err != nil {
				return nil, fmt.Errorf("failed to dial: %w", err)
			}
			return conn, nil
		},
		TLSHandshakeTimeout: 10 * time.Second,
		TLSClientConfig: &tls.Config{
			InsecureSkipVerify: true,
		},
	}

	client := &http.Client{
		Transport: transport,
		Timeout:   30 * time.Second,
	}

	return client, nil
}

func newAuthWithUser(user *url.Userinfo) *proxy.Auth {
	if username, password, ok := splitAuth(user); ok {
		return &proxy.Auth{
			User:     username,
			Password: password,
		}
	}
	return nil
}

func splitAuth(u *url.Userinfo) (user, password string, ok bool) {
	user = u.Username()
	if password, ok = u.Password(); !ok {
		return
	}
	return
}
