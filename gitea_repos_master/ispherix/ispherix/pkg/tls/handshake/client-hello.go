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

type ClientHello struct {
	ClientVersion      types.ProtocolVersion     `json:"client_version"`
	Random             Random                    `json:"random"`
	SessionID          []byte                    `json:"session_id"`
	CipherSuites       []types.CipherSuite       `json:"cipher_suites"`
	CompressionMethods []types.CompressionMethod `json:"compression_methods"`
	Extensions         extension.Extensions      `json:"extensions,omitempty"`
}

func (c *ClientHello) Parse(b []byte) error {
	br := bytes.NewReader(b)

	if err := binary.Read(br, binary.BigEndian, &c.ClientVersion); err != nil {
		return fmt.Errorf("failed to read client_version: %w", err)
	}

	if _, err := br.Read(c.Random[:]); err != nil {
		return fmt.Errorf("failed to read random: %w", err)
	}

	sessionIDBytesLength, err := br.ReadByte()
	if err != nil {
		return fmt.Errorf("failed to read session_id length: %w", err)
	}

	c.SessionID = make([]byte, sessionIDBytesLength)
	if _, err = br.Read(c.SessionID); err != nil {
		return fmt.Errorf("failed to read session_id: %w", err)
	}

	var cipherSuitesBytesLength uint16
	if err = binary.Read(br, binary.BigEndian, &cipherSuitesBytesLength); err != nil {
		return fmt.Errorf("failed to read cipher_suites length: %w", err)
	}

	c.CipherSuites = make([]types.CipherSuite, cipherSuitesBytesLength/2)
	for i := uint16(0); i < cipherSuitesBytesLength/2; i++ {
		var cipherSuite uint16
		if err = binary.Read(br, binary.BigEndian, &cipherSuite); err != nil {
			return fmt.Errorf("failed to read cipher_suites: %w", err)
		}
		c.CipherSuites[i] = types.CipherSuite(cipherSuite)
	}

	compressionMethodsBytesLength, err := br.ReadByte()
	if err != nil {
		return fmt.Errorf("failed to read compression_methods length: %w", err)
	}

	c.CompressionMethods = make([]types.CompressionMethod, compressionMethodsBytesLength)
	for i := uint8(0); i < compressionMethodsBytesLength; i++ {
		compressionMethod, err := br.ReadByte()
		if err != nil {
			return fmt.Errorf("failed to read compression_methods: %w", err)
		}
		c.CompressionMethods[i] = types.CompressionMethod(compressionMethod)
	}

	var extensionsBytesLength uint16
	if err = binary.Read(br, binary.BigEndian, &extensionsBytesLength); err != nil {
		return fmt.Errorf("failed to read extensions: %w", err)
	}

	extensionsBytes := make([]byte, extensionsBytesLength)
	if _, err = br.Read(extensionsBytes); err != nil {
		return fmt.Errorf("failed to read extensions: %w", err)
	}

	if err = c.Extensions.Parse(extensionsBytes); err != nil {
		return fmt.Errorf("failed to parse extensions: %w", err)
	}

	return nil
}

func (c *ClientHello) JA3() string {
	var sb strings.Builder

	// Client version
	sb.WriteString(strconv.FormatUint(uint64(c.ClientVersion), 10))
	sb.WriteRune(',')

	// Client cipher suites
	cipherSuites := make([]string, 0, len(c.CipherSuites))
	for _, cipher := range c.CipherSuites {
		if utils.NotGrease(uint16(cipher)) {
			cipherSuites = append(cipherSuites, strconv.FormatUint(uint64(cipher), 10))
		}
	}

	sb.WriteString(strings.Join(cipherSuites, "-"))
	sb.WriteRune(',')

	// Client extensions
	extensions := make([]string, 0, len(c.Extensions))
	var supportedGroups []string
	var ecPointFormats []string

	for _, ext := range c.Extensions {
		if utils.NotGrease(uint16(ext.Type)) {
			extensions = append(extensions, strconv.FormatUint(uint64(ext.Type), 10))
		}

		switch extContent := ext.Content.(type) {
		case *extension.SupportedGroups:
			supportedGroups = make([]string, 0, len(extContent.Curves))
			for _, supportedGroup := range extContent.Curves {
				if utils.NotGrease(uint16(supportedGroup)) {
					supportedGroups = append(supportedGroups, strconv.FormatUint(uint64(supportedGroup), 10))
				}
			}

		case *extension.ECPointFormats:
			ecPointFormats = make([]string, 0, len(extContent.Formats))
			for _, f := range extContent.Formats {
				ecPointFormats = append(ecPointFormats, strconv.FormatUint(uint64(f), 10))
			}
		}
	}

	sb.WriteString(strings.Join(extensions, "-"))
	sb.WriteRune(',')

	sb.WriteString(strings.Join(supportedGroups, "-"))
	sb.WriteRune(',')

	sb.WriteString(strings.Join(ecPointFormats, "-"))

	return sb.String()
}
