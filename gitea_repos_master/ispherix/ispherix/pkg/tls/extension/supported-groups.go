package extension

import (
	"encoding/binary"

	"go.i-sphere.ru/ispherix/pkg/tls/types"
)

type SupportedGroups struct {
	Curves []types.Curve `json:"curves"`
}

func (g *SupportedGroups) Parse(b []byte) error {
	g.Curves = make([]types.Curve, 0, binary.BigEndian.Uint16(b[0:2])/2)
	for i := 0; i < cap(g.Curves); i++ {
		g.Curves = append(g.Curves, types.Curve(binary.BigEndian.Uint16(b[2+i*2:4+i*2])))
	}
	return nil
}
