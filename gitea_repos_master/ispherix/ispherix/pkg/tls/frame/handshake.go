package frame

import (
	"errors"
	"fmt"

	"go.i-sphere.ru/ispherix/pkg/tls/handshake"
	"go.i-sphere.ru/ispherix/pkg/tls/types"
)

type Handshake struct {
	Type    types.HandshakeType    `json:"type"`
	Content types.HandshakeContent `json:"content,omitempty"`
}

func (h *Handshake) Parse(b []byte) error {
	if len(b) < 4 {
		return errors.New("invalid handshake length")
	}

	h.Type = types.HandshakeType(b[0])

	switch h.Type {
	case types.HandshakeTypeClientHello:
		h.Content = &handshake.ClientHello{}
	case types.HandshakeTypeServerHello:
		h.Content = &handshake.ServerHello{}
	}

	if h.Content != nil {
		if err := h.Content.Parse(b[4:]); err != nil {
			return fmt.Errorf("parse handshake content: %w", err)
		}
	}

	return nil
}
