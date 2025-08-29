package socks5

import (
	"fmt"
	"io"

	"go.i-sphere.ru/proxy/pkg/utils"
)

type ServerNegotiation struct {
	NegotiationVersion NegotiationVersion
	NegotiationStatus  NegotiationStatus
}

func NewServerNegotiation(negotiationStatus NegotiationStatus) *ServerNegotiation {
	return &ServerNegotiation{
		NegotiationVersion: SubNegotiationVersion,
		NegotiationStatus:  negotiationStatus,
	}
}

func MarshalServerNegotiation(writer io.Writer, serverNegotiation *ServerNegotiation) error {
	serverNegotiationBytes := []byte{
		byte(serverNegotiation.NegotiationVersion),
		byte(serverNegotiation.NegotiationStatus),
	}

	if err := utils.Must(writer.Write(serverNegotiationBytes)); err != nil {
		return fmt.Errorf("failed to write server negotiation: %w", err)
	}
	return nil
}
