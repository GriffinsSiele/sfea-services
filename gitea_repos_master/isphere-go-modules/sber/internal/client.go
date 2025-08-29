package internal

import (
	"crypto/tls"
	"fmt"
	"net/http"
	"net/url"
	"os"

	"golang.org/x/net/proxy"
)

func NewClient() (*http.Client, error) {
	proxyURL, err := url.Parse(os.Getenv("PROXY_URL"))
	if err != nil {
		return nil, fmt.Errorf("failed to parse proxy url: %v", err)
	}

	if proxyURL.Scheme != "socks5" {
		return nil, fmt.Errorf("unsupported proxy scheme: %s", proxyURL.Scheme)
	}

	dialer, err := proxy.SOCKS5("tcp", proxyURL.Host, nil, proxy.Direct)
	if err != nil {
		return nil, fmt.Errorf("failed to create dialer: %v", err)
	}

	transport := &http.Transport{
		Dial: dialer.Dial,
		TLSClientConfig: &tls.Config{
			InsecureSkipVerify: true,
		},
	}

	client := &http.Client{
		Transport: transport,
	}

	return client, nil
}
