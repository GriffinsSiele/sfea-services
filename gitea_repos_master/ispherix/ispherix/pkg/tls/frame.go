package tls

import (
	"fmt"
	"net"

	"go.i-sphere.ru/ispherix/pkg/tls/frame"
	"go.i-sphere.ru/ispherix/pkg/tls/types"
)

type Frame struct {
	Source          net.Conn              `json:"-"`
	Destination     net.Conn              `json:"-"`
	Type            types.FrameType       `json:"type"`
	ProtocolVersion types.ProtocolVersion `json:"protocol_version"`
	Content         types.FrameContent    `json:"content,omitempty"`
}

func (f *Frame) Parse(b []byte) error {
	switch f.Type {
	case types.FrameTypeHandshake:
		f.Content = &frame.Handshake{}
	}

	if f.Content != nil {
		if err := f.Content.Parse(b); err != nil {
			return fmt.Errorf("failed to parse frame content: %w", err)
		}
	}

	return nil
}

func (f *Frame) DirectionFor(src net.Conn) rune {
	if f.Source == src {
		return '>'
	} else {
		return '<'
	}
}
