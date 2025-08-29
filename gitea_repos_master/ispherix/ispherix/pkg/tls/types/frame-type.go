package types

import (
	"fmt"
	"strconv"
)

type FrameType uint8

const (
	FrameTypeChangeCipherSpec FrameType = 20
	FrameTypeAlert            FrameType = 21
	FrameTypeHandshake        FrameType = 22
	FrameTypeApplicationData  FrameType = 23
)

func (c *FrameType) String() string {
	switch *c {
	case FrameTypeChangeCipherSpec:
		return "change_cipher_spec"
	case FrameTypeAlert:
		return "alert"
	case FrameTypeHandshake:
		return "handshake"
	case FrameTypeApplicationData:
		return "application_data"
	default:
		return fmt.Sprintf("0x%02x", *c)
	}
}

func (c *FrameType) MarshalJSON() ([]byte, error) {
	return []byte(strconv.Quote(c.String())), nil
}
