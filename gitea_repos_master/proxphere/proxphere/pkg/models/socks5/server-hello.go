package socks5

import (
	"fmt"
	"io"

	"go.i-sphere.ru/proxy/pkg/utils"
)

type ServerHello struct {
	Version              Version
	AuthenticationMethod AuthenticationMethod
}

func NewServerHello(authenticationMethod AuthenticationMethod) *ServerHello {
	return &ServerHello{
		Version:              SOCKS5,
		AuthenticationMethod: authenticationMethod,
	}
}

func MarshalServerHello(writer io.Writer, serverHello *ServerHello) error {
	serverHelloBytes := []byte{
		byte(serverHello.Version),
		byte(serverHello.AuthenticationMethod),
	}

	if err := utils.Must(writer.Write(serverHelloBytes)); err != nil {
		return fmt.Errorf("failed to write response: %w", err)
	}
	return nil
}
