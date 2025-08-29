package extension

import (
	"bytes"
	"encoding/binary"
	"errors"
	"fmt"
	"io"

	"go.i-sphere.ru/ispherix/pkg/tls/types"
)

type Extensions []*Extension

func (e *Extensions) Parse(b []byte) error {
	br := bytes.NewReader(b)

	for {
		var extensionType uint16
		if err := binary.Read(br, binary.BigEndian, &extensionType); err != nil {
			if errors.Is(err, io.EOF) {
				break
			}
			return fmt.Errorf("failed to read extension type: %w", err)
		}

		var extensionContentLength uint16
		if err := binary.Read(br, binary.BigEndian, &extensionContentLength); err != nil {
			return fmt.Errorf("failed to read extension content length: %w", err)
		}

		extensionContentBytes := make([]byte, extensionContentLength)
		if _, err := br.Read(extensionContentBytes); err != nil {
			return fmt.Errorf("failed to read extension content: %w", err)
		}

		extension := &Extension{Type: types.ExtensionType(extensionType)}
		if err := extension.Parse(extensionContentBytes); err != nil {
			return fmt.Errorf("failed to parse extension: %w", err)
		}

		*e = append(*e, extension)
	}

	return nil
}
