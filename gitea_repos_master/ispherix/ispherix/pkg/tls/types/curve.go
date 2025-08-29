package types

import "fmt"

type Curve uint16

const (
	CurveSECP256r1 Curve = 23
	CurveSECP384r1 Curve = 24
	CurveSECP521r1 Curve = 25
	CurveX25519    Curve = 29
	CurveX448      Curve = 30
)

func (c *Curve) String() string {
	switch *c {
	case CurveSECP256r1:
		return "secp256r1"
	case CurveSECP384r1:
		return "secp384r1"
	case CurveSECP521r1:
		return "secp521r1"
	case CurveX25519:
		return "x25519"
	case CurveX448:
		return "x448"
	default:
		return fmt.Sprintf("0x%04x", *c)
	}
}
