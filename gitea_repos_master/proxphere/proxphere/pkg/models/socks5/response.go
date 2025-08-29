package socks5

import (
	"fmt"
	"io"

	"go.i-sphere.ru/proxy/pkg/utils"
)

type Response struct {
	Version Version
	Status  ConnectionStatus
}

func NewResponse(status ConnectionStatus) *Response {
	return &Response{
		Version: SOCKS5,
		Status:  status,
	}
}

func MarshalResponse(writer io.Writer, serverConnect *Response) error {
	serverConnectBytes := []byte{
		byte(serverConnect.Version),
		byte(serverConnect.Status),
		Reserved,
		byte(IPv4),
		Reserved, Reserved, Reserved, Reserved, // IPv4 address
		Reserved, Reserved, // Port
	}

	if err := utils.Must(writer.Write(serverConnectBytes)); err != nil {
		return fmt.Errorf("failed to write response: %w", err)
	}
	return nil
}
