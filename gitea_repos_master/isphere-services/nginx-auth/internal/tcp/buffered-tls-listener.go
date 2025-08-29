package tcp

import (
	"crypto/tls"
	"fmt"
	"net"
)

type BufferedTLSListener struct {
	net.Listener
	config *tls.Config
}

func NewBufferedTLSListener(listener net.Listener, config *tls.Config) net.Listener {
	return &BufferedTLSListener{
		Listener: listener,
		config:   config,
	}
}

func (l *BufferedTLSListener) Accept() (net.Conn, error) {
	conn, err := l.Listener.Accept()
	if err != nil {
		return nil, fmt.Errorf("failed to accept TCP connection: %w", err)
	}

	buffConn := NewBufferedConn(conn)

	return tls.Server(buffConn, l.config), nil
}
