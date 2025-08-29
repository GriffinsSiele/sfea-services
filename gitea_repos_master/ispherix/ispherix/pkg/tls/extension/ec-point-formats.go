package extension

import "go.i-sphere.ru/ispherix/pkg/tls/types"

type ECPointFormats struct {
	Formats []types.ECPointFormat `json:"formats"`
}

func (f *ECPointFormats) Parse(b []byte) error {
	f.Formats = make([]types.ECPointFormat, 0, b[0])
	b = b[1:] // skip length
	for len(b) > 0 {
		f.Formats = append(f.Formats, types.ECPointFormat(b[0]))
		b = b[1:]
	}

	return nil
}
