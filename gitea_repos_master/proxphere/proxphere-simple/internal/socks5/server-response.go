package socks5

import (
	"context"
	"fmt"
	"net"
)

type ServerResponse struct {
	Status ResponseStatus
}

func (s *ServerResponse) Write(_ context.Context, conn net.Conn) error {
	if _, err := conn.Write([]byte{
		0x05,
		byte(s.Status),
		0x00,
		byte(AddressIPv4),
		0x00, 0x00, 0x00, 0x00,
		0x00, 0x00,
	}); err != nil {
		return fmt.Errorf("failed to write: %w", err)
	}

	return nil
}
