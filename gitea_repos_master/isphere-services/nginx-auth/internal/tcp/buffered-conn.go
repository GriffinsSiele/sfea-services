package tcp

import (
	"bytes"
	"fmt"
	"net"
)

type BufferedConn struct {
	net.Conn
	buffer *bytes.Buffer
}

func NewBufferedConn(c net.Conn) *BufferedConn {
	return &BufferedConn{
		Conn:   c,
		buffer: bytes.NewBuffer(make([]byte, 0)),
	}
}

// Write Server -> Client
func (c *BufferedConn) Write(data []byte) (int, error) {
	n, err := c.Conn.Write(data)
	if err != nil {
		return 0, fmt.Errorf("failed to write into TLS connection: %w", err)
	}

	return n, nil
}

// Read Client -> Server
func (c *BufferedConn) Read(data []byte) (int, error) {
	if _, err := c.buffer.Write(data); err != nil {
		return 0, fmt.Errorf("failed to write into buffer: %w", err)
	}

	n, err := c.Conn.Read(data)
	if err != nil {
		return 0, fmt.Errorf("failed to read from TLS connection: %w", err)
	}

	return n, nil
}

func (c *BufferedConn) Bytes() []byte {
	return c.buffer.Bytes()
}
