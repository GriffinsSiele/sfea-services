package socks5

import (
	"context"
	"fmt"
	"net"
)

type DestinationAddress struct {
	Type AddressType
	Addr []byte
}

func (a *DestinationAddress) Read(_ context.Context, conn net.Conn) error {
	typeBytes := make([]byte, 1)
	if _, err := conn.Read(typeBytes); err != nil {
		return fmt.Errorf("failed to read type bytes: %w", err)
	}

	a.Type = AddressType(typeBytes[0])

	switch a.Type {
	case AddressIPv4:
		ipV4 := make([]byte, 4)
		if _, err := conn.Read(ipV4); err != nil {
			return fmt.Errorf("failed to read IPv4 bytes: %w", err)
		}

		a.Addr = []byte(net.IP(ipV4).String())

	case AddressIPv6:
		ipV6 := make([]byte, 16)
		if _, err := conn.Read(ipV6); err != nil {
			return fmt.Errorf("failed to read IPv6 bytes: %w", err)
		}

		a.Addr = []byte(net.IP(ipV6).String())

	case AddressDomain:
		domainLengthBytes := make([]byte, 1)
		if _, err := conn.Read(domainLengthBytes); err != nil {
			return fmt.Errorf("failed to read domain length bytes: %w", err)
		}

		a.Addr = make([]byte, domainLengthBytes[0])
		if _, err := conn.Read(a.Addr); err != nil {
			return fmt.Errorf("failed to read domain bytes: %w", err)
		}

	default:
		return ErrInvalidAddressType
	}

	return nil
}
