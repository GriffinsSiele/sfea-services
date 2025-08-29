package socks5

import (
	"encoding/binary"
	"fmt"
	"io"
	"net"
	"strconv"
)

type ClientRequest struct {
	Version       uint8
	Command       uint8
	TargetAddress string
	TargetPort    uint
}

func (t *ClientRequest) UnmarshalReader(reader io.Reader) error {
	var versionBytes [1]byte
	if _, err := reader.Read(versionBytes[:]); err != nil {
		return fmt.Errorf("failed to read version: %w", err)
	}
	t.Version = versionBytes[0]

	var commandBytes [1]byte
	if _, err := reader.Read(commandBytes[:]); err != nil {
		return fmt.Errorf("failed to read command: %w", err)
	}
	t.Command = commandBytes[0]

	if _, err := reader.Read([]byte{0x00}); err != nil {
		return fmt.Errorf("failed to read padding: %w", err)
	}

	var addressTypeBytes [1]byte
	if _, err := reader.Read(addressTypeBytes[:]); err != nil {
		return fmt.Errorf("failed to read address type: %w", err)
	}

	switch addressTypeBytes[0] {
	case 0x01: // IPv4
		var ipv4Bytes [4]byte
		if _, err := reader.Read(ipv4Bytes[:]); err != nil {
			return fmt.Errorf("failed to read IPv4: %w", err)
		}
		t.TargetAddress = net.IP(ipv4Bytes[:]).String()
	case 0x03: // domain name
		var domainLengthBytes [1]byte
		if _, err := reader.Read(domainLengthBytes[:]); err != nil {
			return fmt.Errorf("failed to read domain length: %w", err)
		}
		domainBytes := make([]byte, domainLengthBytes[0])
		if _, err := reader.Read(domainBytes); err != nil {
			return fmt.Errorf("failed to read domain: %w", err)
		}
		t.TargetAddress = string(domainBytes)
	case 0x04: // IPv6
		var ipv6Bytes [16]byte
		if _, err := reader.Read(ipv6Bytes[:]); err != nil {
			return fmt.Errorf("failed to read IPv6: %w", err)
		}
		t.TargetAddress = net.IP(ipv6Bytes[:]).String()
	default:
		return fmt.Errorf("unsupported address type: %d", addressTypeBytes[0])
	}

	var portBytes [2]byte
	if _, err := reader.Read(portBytes[:]); err != nil {
		return fmt.Errorf("failed to read port: %w", err)
	}
	t.TargetPort = uint(binary.BigEndian.Uint16(portBytes[:]))

	return nil
}

func (t *ClientRequest) Addr() string {
	return net.JoinHostPort(t.TargetAddress, strconv.Itoa(int(t.TargetPort)))
}
