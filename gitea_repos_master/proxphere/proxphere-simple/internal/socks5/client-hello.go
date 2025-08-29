package socks5

import (
	"context"
	"fmt"
	"net"
)

type ClientHello struct {
	AcceptMethods []Method
}

func (c *ClientHello) Read(_ context.Context, conn net.Conn) error {
	headerBytes := make([]byte, 2)
	if _, err := conn.Read(headerBytes); err != nil {
		return fmt.Errorf("failed to read header bytes: %w", err)
	}

	if headerBytes[0] != 0x05 {
		return fmt.Errorf("unsupported SOCKS5 version %x", headerBytes[0])
	}

	acceptMethods := make([]byte, headerBytes[1])
	if _, err := conn.Read(acceptMethods); err != nil {
		return fmt.Errorf("failed to read client methods: %w", err)
	}

	c.AcceptMethods = make([]Method, len(acceptMethods))
	for i, method := range acceptMethods {
		c.AcceptMethods[i] = Method(method)
	}

	return nil
}
