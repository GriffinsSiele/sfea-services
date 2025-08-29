package socks5

import (
	"fmt"
	"io"
)

type ClientHello struct {
	Version               Version
	AuthenticationMethods []AuthenticationMethod
}

func UnmarshalClientHello(reader io.Reader, clientHello *ClientHello) error {
	header := make([]byte, 2)
	if n, err := reader.Read(header); err != nil || n != len(header) {
		return fmt.Errorf("failed to read header: %w", err)
	}

	clientHello.Version = Version(header[0])
	if clientHello.Version != SOCKS5 {
		return fmt.Errorf("unsupported SOCKS version: %d", clientHello.Version)
	}

	authenticationMethodsBytes := make([]byte, header[1])
	if n, err := reader.Read(authenticationMethodsBytes); err != nil || n != cap(authenticationMethodsBytes) {
		return fmt.Errorf("failed to read authentication methods: %w", err)
	}

	clientHello.AuthenticationMethods = make([]AuthenticationMethod, len(authenticationMethodsBytes))
	for i, b := range authenticationMethodsBytes {
		clientHello.AuthenticationMethods[i] = AuthenticationMethod(b)
	}

	return nil
}
