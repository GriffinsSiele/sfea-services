package handshake

import (
	"bytes"
	"encoding/binary"
	"fmt"
	"strconv"
	"strings"

	"go.i-sphere.ru/ispherix/pkg/tls/extension"
	"go.i-sphere.ru/ispherix/pkg/tls/types"
	"go.i-sphere.ru/ispherix/pkg/tls/utils"
)

type ServerHello struct {
	ServerVersion     types.ProtocolVersion   `json:"server_version"`
	Random            Random                  `json:"random"`
	SessionID         []byte                  `json:"session_id"`
	CipherSuite       types.CipherSuite       `json:"cipher_suite"`
	CompressionMethod types.CompressionMethod `json:"compression_method"`
	Extensions        extension.Extensions    `json:"extensions,omitempty"`
}

func (s *ServerHello) Parse(b []byte) error {
	br := bytes.NewReader(b)

	if err := binary.Read(br, binary.BigEndian, &s.ServerVersion); err != nil {
		return fmt.Errorf("parse server version: %w", err)
	}

	if _, err := br.Read(s.Random[:]); err != nil {
		return err
	}

	sessionIDBytesLength, err := br.ReadByte()
	if err != nil {
		return fmt.Errorf("parse session id length: %w", err)
	}

	s.SessionID = make([]byte, sessionIDBytesLength)
	if _, err = br.Read(s.SessionID); err != nil {
		return fmt.Errorf("parse session id: %w", err)
	}

	if err = binary.Read(br, binary.BigEndian, &s.CipherSuite); err != nil {
		return fmt.Errorf("parse cipher suite: %w", err)
	}

	compressionMethodByte, err := br.ReadByte()
	if err != nil {
		return fmt.Errorf("parse compression method: %w", err)
	}
	s.CompressionMethod = types.CompressionMethod(compressionMethodByte)

	var extensionsBytesLength uint16
	if err = binary.Read(br, binary.BigEndian, &extensionsBytesLength); err != nil {
		return fmt.Errorf("parse extensions length: %w", err)
	}

	extensionsBytes := make([]byte, extensionsBytesLength)
	if _, err = br.Read(extensionsBytes); err != nil {
		return fmt.Errorf("parse extensions: %w", err)
	}

	if err = s.Extensions.Parse(extensionsBytes); err != nil {
		return fmt.Errorf("parse extensions: %w", err)
	}

	return nil
}

func (s *ServerHello) JA3S() string {
	var sb strings.Builder

	// Server version
	sb.WriteString(strconv.FormatUint(uint64(s.ServerVersion), 10))
	sb.WriteRune(',')

	// Server cipher suite
	sb.WriteString(strconv.FormatUint(uint64(s.CipherSuite), 10))
	sb.WriteRune(',')

	// Server extensions
	extensions := make([]string, 0, len(s.Extensions))
	for _, ext := range s.Extensions {
		if utils.NotGrease(uint16(ext.Type)) {
			extensions = append(extensions, strconv.FormatUint(uint64(ext.Type), 10))
		}
	}

	sb.WriteString(strings.Join(extensions, "-"))

	return sb.String()
}
