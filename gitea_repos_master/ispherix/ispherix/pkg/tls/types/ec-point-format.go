package types

import "fmt"

type ECPointFormat uint8

const PointFormatUncompressed ECPointFormat = 0

func (f *ECPointFormat) String() string {
	switch *f {
	case PointFormatUncompressed:
		return "uncompressed"
	default:
		return fmt.Sprintf("0x%04x", *f)
	}
}
