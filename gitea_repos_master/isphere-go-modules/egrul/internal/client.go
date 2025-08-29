package internal

import (
	"context"
	"crypto/tls"
	"net"
	"net/http"
	"net/url"
	"os"
	"time"

	"github.com/pkg/errors"
	"golang.org/x/net/proxy"
)

func NewClient() (*http.Client, error) {
	proxyURL, err := url.Parse(os.Getenv("PROXY_URL"))
	if err != nil {
		return nil, errors.Wrap(err, "failed to parse proxy url")
	}
	if proxyURL.Scheme != "socks5" {
		return nil, errors.Wrap(err, "proxy url scheme must be socks5")
	}

	dialer, err := proxy.SOCKS5("tcp", proxyURL.Host, newAuthWithUser(proxyURL.User), proxy.Direct)
	if err != nil {
		return nil, errors.Wrap(err, "failed to create dialer")
	}

	transport := http.Transport{
		DialContext: func(tx context.Context, network, addr string) (net.Conn, error) {
			conn, err := dialer.Dial(network, addr)
			if err != nil {
				return nil, errors.Wrap(err, "failed to dial")
			}
			return conn, nil
		},
		TLSHandshakeTimeout: 10 * time.Second,
		TLSClientConfig: &tls.Config{
			InsecureSkipVerify: true,
		},
	}

	client := http.Client{
		Transport: &transport,
		Timeout:   30 * time.Second,
	}

	return &client, nil
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
