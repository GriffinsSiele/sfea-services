package socks5

import (
	"context"
	"fmt"
	"net"
)

type ServerAuthenticationResponse struct {
	Status AuthenticationStatus
}

func (s *ServerAuthenticationResponse) Write(_ context.Context, conn net.Conn) error {
	if _, err := conn.Write([]byte{
		0x01,
		byte(s.Status),
	}); err != nil {
		return fmt.Errorf("failed to write: %w", err)
	}

	return nil
}
