package model

import (
	"net"
	"strconv"
)

const (
	DefaultHTTPServeHost = ""
	DefaultHTTPServePort = 3000
)

type HTTPServeFlags struct {
	Host string
	Port int
}

func (t *HTTPServeFlags) GetAddr() string {
	return net.JoinHostPort(t.Host, strconv.Itoa(t.Port))
}
