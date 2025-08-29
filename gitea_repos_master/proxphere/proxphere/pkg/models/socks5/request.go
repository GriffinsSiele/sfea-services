package socks5

import (
	"encoding/binary"
	"errors"
	"fmt"
	"io"
	"net"
	"strconv"
)

var AddressTypeNotSupportedErr = errors.New("address type not supported")
var CommandNotSupportedErr = errors.New("command not supported")

type Request struct {
	Version            Version
	Command            Command
	AddressType        AddressType
	DestinationAddress string
}

func UnmarshalRequest(reader io.Reader, request *Request) error {
	headerBytes := make([]byte, 4)
	if n, err := reader.Read(headerBytes); err != nil || n != len(headerBytes) {
		return fmt.Errorf("failed to read header: %w", err)
	}

	request.Version = Version(headerBytes[0])
	if request.Version != SOCKS5 {
		return fmt.Errorf("unsupported SOCKS version: %d", request.Version)
	}

	request.Command = Command(headerBytes[1])
	if request.Command != Connect {
		return CommandNotSupportedErr
	}

	if headerBytes[2] != Reserved {
		return fmt.Errorf("unsupported reserved byte: %d", headerBytes[2])
	}

	var hostname string

	request.AddressType = AddressType(headerBytes[3])
	switch request.AddressType {
	case IPv4:
		ipv4 := make(net.IP, 4)
		if n, err := reader.Read(ipv4); err != nil || n != len(ipv4) {
			return fmt.Errorf("failed to read IPv4 address: %w", err)
		}

		hostname = ipv4.String()

	case IPv6:
		ipv6 := make(net.IP, 16)
		if n, err := reader.Read(ipv6); err != nil || n != len(ipv6) {
			return fmt.Errorf("failed to read IPv6 address: %w", err)
		}

		hostname = ipv6.String()

	case Domain:
		lengthBytes := make([]byte, 1)
		if n, err := reader.Read(lengthBytes); err != nil || n != len(lengthBytes) {
			return fmt.Errorf("failed to read hostname length: %w", err)
		}

		hostnameBytes := make([]byte, lengthBytes[0])
		if n, err := reader.Read(hostnameBytes); err != nil || n != len(hostnameBytes) {
			return fmt.Errorf("failed to read hostname: %w", err)
		}

		hostname = string(hostnameBytes)

	default:
		return AddressTypeNotSupportedErr
	}

	var port uint16
	if err := binary.Read(reader, binary.BigEndian, &port); err != nil {
		return fmt.Errorf("failed to read port: %w", err)
	}

	request.DestinationAddress = net.JoinHostPort(hostname, strconv.FormatUint(uint64(port), 10))

	return nil
}
