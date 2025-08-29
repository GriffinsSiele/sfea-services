package socks5

import (
	"context"
	"fmt"
	"net"
)

type ClientAuthenticationRequest struct {
	Username []byte
	Password []byte
}

func (c *ClientAuthenticationRequest) Read(ctx context.Context, conn net.Conn) error {
	headerBytes := make([]byte, 2)
	if _, err := conn.Read(headerBytes); err != nil {
		return fmt.Errorf("failed to read header bytes: %w", err)
	}

	if headerBytes[0] != 0x01 {
		return fmt.Errorf("unsupported negotiation protocol version: %x", headerBytes[0])
	}

	c.Username = make([]byte, headerBytes[1])
	if _, err := conn.Read(c.Username); err != nil {
		return fmt.Errorf("failed to read username bytes: %w", err)
	}

	passwordLengthBytes := make([]byte, 1)
	if _, err := conn.Read(passwordLengthBytes); err != nil {
		return fmt.Errorf("failed to read password length bytes: %w", err)
	}

	c.Password = make([]byte, passwordLengthBytes[0])
	if _, err := conn.Read(c.Password); err != nil {
		return fmt.Errorf("failed to read password bytes: %w", err)
	}

	return nil
}
