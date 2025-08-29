package types

import (
	"fmt"
	"strconv"
)

type CompressionMethod uint8

const CompressionMethodNone CompressionMethod = 0

func (c *CompressionMethod) String() string {
	switch *c {
	case CompressionMethodNone:
		return "none"
	default:
		return fmt.Sprintf("0x%02x", *c)
	}
}

func (c *CompressionMethod) MarshalJSON() ([]byte, error) {
	return []byte(strconv.Quote(c.String())), nil
}
