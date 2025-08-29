package socks5

import (
	"fmt"
	"io"
)

type ClientNegotiation struct {
	NegotiationVersion NegotiationVersion
	Username           string
	Password           string
}

func UnmarshalClientNegotiation(reader io.Reader, clientNegotiation *ClientNegotiation) error {
	header := make([]byte, 2)
	if n, err := reader.Read(header); err != nil || n != len(header) {
		return fmt.Errorf("failed to read header: %w", err)
	}

	clientNegotiation.NegotiationVersion = NegotiationVersion(header[0])
	if clientNegotiation.NegotiationVersion != SubNegotiationVersion {
		return fmt.Errorf("unsupported subnegotiation version: %d", clientNegotiation.NegotiationVersion)
	}

	usernameLength := header[1]
	usernameBytes := make([]byte, usernameLength)
	if n, err := reader.Read(usernameBytes); err != nil || n != len(usernameBytes) {
		return fmt.Errorf("failed to read username: %w", err)
	}
	clientNegotiation.Username = string(usernameBytes)

	passwordLengthBytes := make([]byte, 1)
	if n, err := reader.Read(passwordLengthBytes); err != nil || n != len(passwordLengthBytes) {
		return fmt.Errorf("failed to read password length: %w", err)
	}
	passwordLength := int(passwordLengthBytes[0])
	passwordBytes := make([]byte, passwordLength)
	if n, err := reader.Read(passwordBytes); err != nil || n != len(passwordBytes) {
		return fmt.Errorf("failed to read password: %w", err)
	}
	clientNegotiation.Password = string(passwordBytes)

	return nil
}
