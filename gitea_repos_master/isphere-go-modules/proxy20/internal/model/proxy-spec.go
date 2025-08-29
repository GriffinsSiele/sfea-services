package model

import (
	"encoding/base64"
	"net"
	"net/url"
	"strconv"
)

type ProxySpec struct {
	ID         int    `yaml:"id"         json:"id"`
	Host       string `yaml:"server"     json:"server"`
	Port       int    `yaml:"port"       json:"port"`
	Username   string `yaml:"login"      json:"login"`
	Password   string `yaml:"password"   json:"password"`
	ProxyGroup int    `yaml:"proxygroup" json:"proxygroup"`
	RegionCode string `yaml:"country"    json:"country"`

	Enabled bool `yaml:"-"`
}

func (t *ProxySpec) SetEnabled(enabled bool) *ProxySpec {
	t.Enabled = enabled
	return t
}

func (t *ProxySpec) Addr() string {
	return net.JoinHostPort(t.Host, strconv.Itoa(t.Port))
}

func (t *ProxySpec) URL() *url.URL {
	return &url.URL{
		Scheme: "http",
		User:   url.UserPassword(t.Username, t.Password),
		Host:   t.Addr(),
	}
}

func (t *ProxySpec) BasicAuth() string {
	auth := t.Username + ":" + t.Password
	return base64.StdEncoding.EncodeToString([]byte(auth))
}

type ProxySpecWithError struct {
	ProxySpec *ProxySpec
	Error     error
}
