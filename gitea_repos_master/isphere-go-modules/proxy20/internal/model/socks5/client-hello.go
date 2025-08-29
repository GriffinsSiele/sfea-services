package socks5

import (
	"fmt"
	"io"
)

type ClientHello struct {
	Version               uint8
	AuthenticationMethods []uint8
}

func (t *ClientHello) UnmarshalReader(reader io.Reader) error {
	var versionBytes [1]byte
	if _, err := reader.Read(versionBytes[:]); err != nil {
		return fmt.Errorf("failed to read version: %w", err)
	}
	t.Version = versionBytes[0]

	var authenticationMethodsCount [1]byte
	if _, err := reader.Read(authenticationMethodsCount[:]); err != nil {
		return fmt.Errorf("failed to read authentication methods count: %w", err)
	}

	t.AuthenticationMethods = make([]uint8, authenticationMethodsCount[0])
	if _, err := reader.Read(t.AuthenticationMethods); err != nil {
		return fmt.Errorf("failed to read authentication methods: %w", err)
	}

	return nil
}
