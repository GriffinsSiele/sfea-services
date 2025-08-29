package socks5

import (
	"context"
	"fmt"
	"net"
)

type ServerHello struct {
	Method Method
}

func (s *ServerHello) Write(_ context.Context, conn net.Conn) error {
	if _, err := conn.Write([]byte{
		0x05,
		byte(s.Method),
	}); err != nil {
		return fmt.Errorf("failed to write: %w", err)
	}

	return nil
}
