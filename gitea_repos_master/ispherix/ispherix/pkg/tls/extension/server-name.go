package extension

import (
	"bytes"
	"encoding/binary"
	"errors"
	"fmt"
	"io"

	server_name "go.i-sphere.ru/ispherix/pkg/tls/extension/server-name"
	"go.i-sphere.ru/ispherix/pkg/tls/types"
)

type ServerName struct {
	Entries []*server_name.Entry `json:"entries"`
}

func (s *ServerName) Parse(b []byte) error {
	br := bytes.NewReader(b)

	var entriesBytesLength uint16
	if err := binary.Read(br, binary.BigEndian, &entriesBytesLength); err != nil {
		return fmt.Errorf("failed to read entries bytes length: %w", err)
	}

	entriesBytes := make([]byte, entriesBytesLength)
	if _, err := br.Read(entriesBytes); err != nil {
		return fmt.Errorf("failed to read entries bytes: %w", err)
	}

	entriesBytesReader := bytes.NewReader(entriesBytes)

	for {
		nameType, err := entriesBytesReader.ReadByte()
		if err != nil {
			if errors.Is(err, io.EOF) {
				break
			}
			return fmt.Errorf("failed to read name type: %w", err)
		}

		var nameLength uint16
		if err = binary.Read(entriesBytesReader, binary.BigEndian, &nameLength); err != nil {
			return fmt.Errorf("failed to read name length: %w", err)
		}

		nameBytes := make([]byte, nameLength)
		if _, err = entriesBytesReader.Read(nameBytes); err != nil {
			return fmt.Errorf("failed to read name bytes: %w", err)
		}

		s.Entries = append(s.Entries, &server_name.Entry{
			NameType: types.ServerNameType(nameType),
			Name:     string(nameBytes),
		})
	}

	return nil
}
