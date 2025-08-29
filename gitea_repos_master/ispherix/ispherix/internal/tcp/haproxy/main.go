package haproxy

import (
	"bytes"
	"fmt"
	"io"
	"net"
)

const (
	proxy   = "PROXY"
	inet    = "TCP4"
	inet6   = "TCP6"
	unknown = "UNKNOWN"
)

var (
	whiteSpace byte = 0x20
	lineBreak       = []byte{0x0d, 0x0a}
)

func WriteHeader(clientConn, serverConn net.Conn) error {
	headerBuf := bytes.NewBuffer(nil)

	headerBuf.WriteString(proxy)
	headerBuf.WriteByte(whiteSpace)

	switch v := serverConn.RemoteAddr().(type) {
	case *net.TCPAddr:
		switch len(v.IP) {
		case net.IPv4len:
			headerBuf.WriteString(inet)
		case net.IPv6len:
			headerBuf.WriteString(inet6)
		default:
			headerBuf.WriteString(unknown)
		}

		headerBuf.WriteByte(whiteSpace)

	default:
		return fmt.Errorf("unsupported destination remote address type: %T", v)
	}

	clientRemoteHost, clientRemotePort, err := net.SplitHostPort(clientConn.RemoteAddr().String())
	if err != nil {
		return fmt.Errorf("failed to split source address: %w", err)
	}

	serverInternalHost, serverInternalPort, err := net.SplitHostPort(serverConn.LocalAddr().String())
	if err != nil {
		return fmt.Errorf("failed to split destination address: %w", err)
	}

	headerBuf.WriteString(clientRemoteHost)
	headerBuf.WriteByte(whiteSpace)

	headerBuf.WriteString(serverInternalHost)
	headerBuf.WriteByte(whiteSpace)

	headerBuf.WriteString(clientRemotePort)
	headerBuf.WriteByte(whiteSpace)

	headerBuf.WriteString(serverInternalPort)
	headerBuf.Write(lineBreak)

	if _, err = io.Copy(serverConn, headerBuf); err != nil {
		return fmt.Errorf("failed to write header: %w", err)
	}

	return nil
}
