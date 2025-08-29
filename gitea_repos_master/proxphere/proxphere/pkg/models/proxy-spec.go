package models

import (
	"net"
	"net/url"
)

type ProxySpec struct {
	ID          StringifyInt
	JA3         *string
	Group       *ProxySpecGroup
	URL         *url.URL
	CountryCode string
}

func (p *ProxySpec) Addr() string {
	return net.JoinHostPort(p.URL.Hostname(), p.URL.Port())
}

type ProxySpecGroup struct {
	ID StringifyInt
}
