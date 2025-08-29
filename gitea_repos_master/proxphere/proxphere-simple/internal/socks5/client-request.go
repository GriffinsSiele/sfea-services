package socks5

import (
	"context"
	"encoding/binary"
	"fmt"
	"net"
)

type ClientRequest struct {
	Command            Command
	DestinationAddress DestinationAddress
	DestinationPort    uint16
}

func (c *ClientRequest) Read(ctx context.Context, conn net.Conn) error {
	headerBytes := make([]byte, 3)
	if _, err := conn.Read(headerBytes); err != nil {
		return fmt.Errorf("failed to read header bytes: %w", err)
	}

	if headerBytes[0] != 0x05 {
		return fmt.Errorf("unsupported SOCKS5 version %x", headerBytes[0])
	}

	c.Command = Command(headerBytes[1])

	if headerBytes[2] != 0x00 {
		return fmt.Errorf("unexpected reserved byte %x", headerBytes[2])
	}

	if err := c.DestinationAddress.Read(ctx, conn); err != nil {
		return fmt.Errorf("failed to read destination address: %w", err)
	}

	if err := binary.Read(conn, binary.BigEndian, &c.DestinationPort); err != nil {
		return fmt.Errorf("failed to read destination port: %w", err)
	}

	return nil
}
