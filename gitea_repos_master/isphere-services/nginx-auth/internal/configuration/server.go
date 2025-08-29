package configuration

import (
	"net"
	"strconv"
)

type Server struct {
	Scheme string
	Host   string
	Port   uint64
}

func (s *Server) Addr() string {
	portStr := strconv.FormatUint(s.Port, 10)
	return net.JoinHostPort(s.Host, portStr)
}
