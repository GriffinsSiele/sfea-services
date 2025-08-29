package x

// @see https://engineering.salesforce.com/tls-fingerprinting-with-ja3-and-ja3s-247362855967/
// @see https://tls12.xargs.org/#client-hello/annotated
// @see https://www.rfc-editor.org/rfc/rfc8701.html

import (
	"bytes"
	"crypto/tls"
	"encoding/binary"
	"errors"
	"fmt"
	"io"
	"strconv"
	"strings"

	"i-sphere.ru/nginx-auth/internal/contract"
)

type JA3 struct {
	TLSVersion                uint16
	Ciphers                   []uint16
	Extensions                []uint16
	EllipticCurves            []uint16
	EllipticCurvePointFormats []uint8
}

func NewJA3WithReader(reader io.Reader) (*JA3, error) {
	ja3 := &JA3{
		Extensions: make([]uint16, 0),
	}

	handshakeMessageReader, err := ja3.readHandshakeMessage(reader)
	if err != nil {
		return nil, fmt.Errorf("failed to read handshake: %w", err)
	}

	clientHelloReader, err := ja3.readClientHello(handshakeMessageReader)
	if err != nil {
		return nil, fmt.Errorf("failed to read client hello: %w", err)
	}

	extensionsReader, err := ja3.readExtensions(clientHelloReader)
	if err != nil {
		return nil, fmt.Errorf("failed to read extensions: %w", err)
	}

	if err := ja3.readExtension(extensionsReader); err != nil {
		return nil, fmt.Errorf("failed to read extension: %w", err)
	}

	return ja3, nil
}

/*
0x03 0x03   protocol TLS version        uint16
0x00 0xa5   message following length    uint16
...         message                     [N]byte
*/
func (j *JA3) readHandshakeMessage(reader io.Reader) (*bytes.Reader, error) {
	var versionBytes [2]byte
	if _, err := reader.Read(versionBytes[:]); err != nil {
		return nil, fmt.Errorf("failed to read protocol version: %w", err)
	}

	version := binary.BigEndian.Uint16(versionBytes[:])
	if !contract.SupportedVersion(version) {
		return nil, fmt.Errorf("unsupported protocol version: %d", version)
	}

	var messageLenBytes [2]byte
	if _, err := reader.Read(messageLenBytes[:]); err != nil {
		return nil, fmt.Errorf("failed to read handshake message length: %w", err)
	}

	messageLen := binary.BigEndian.Uint16(messageLenBytes[:])

	message := make([]byte, messageLen)
	if _, err := reader.Read(message[:]); err != nil {
		return nil, fmt.Errorf("failed to read handshake message: %w", err)
	}

	return bytes.NewReader(message), nil
}

/*
0x01            client hello type               uint8
0x03 0x03       TLS version                     uint16
0x00 0x00 0xa1  client hello following length   uint24
...             client hello                    [N]byte
*/
func (j *JA3) readClientHello(reader *bytes.Reader) (*bytes.Reader, error) {
	messageType, err := reader.ReadByte()
	if err != nil {
		return nil, fmt.Errorf("failed to read type: %w", err)
	}

	if messageType != clientHelloType {
		return nil, fmt.Errorf("unsupported type: %d", messageType)
	}

	var helloLenTripleBytes [3]byte
	if _, err := reader.Read(helloLenTripleBytes[:]); err != nil {
		return nil, fmt.Errorf("failed to read client hello length: %w", err)
	}

	// emulate uint24 through uint32 with zero-lead [4]byte
	var helloLenFourthBytes [4]byte
	copy(helloLenFourthBytes[1:], helloLenTripleBytes[:])
	helloLen := binary.BigEndian.Uint32(helloLenFourthBytes[:])

	helloBytes := make([]byte, helloLen)
	if _, err := reader.Read(helloBytes[:]); err != nil {
		return nil, fmt.Errorf("failed to read client hello: %w", err)
	}

	return bytes.NewReader(helloBytes), nil
}

/*
0x03 0x03   client TLS version                      uint16          [+]
...         client random                           [32]byte
0x00        session id following length             uint8
...         session id                              [N]byte
0x00 0x20   cipher suites following length          uint16
...         cipher suites one by one                [N/2]uint16     [+]
0x01        compression methods following length    uint8
...         compression methods one by one          [N]uint8
0x00 0x10   extensions following length             uint16
...         extensions                              [N]byte
*/
func (j *JA3) readExtensions(reader *bytes.Reader) (*bytes.Reader, error) {
	var versionBytes [2]byte
	if _, err := reader.Read(versionBytes[:]); err != nil {
		return nil, fmt.Errorf("failed to read client version: %w", err)
	}

	version := binary.BigEndian.Uint16(versionBytes[:])

	if !contract.SupportedVersion(version) {
		return nil, fmt.Errorf("unsupported client version: %d", version)
	}

	j.TLSVersion = version // [+]

	var clientRandomBytes [32]byte
	if _, err := reader.Read(clientRandomBytes[:]); err != nil {
		return nil, fmt.Errorf("failed to read client random: %w", err)
	}

	sessionIDLen, err := reader.ReadByte()
	if err != nil {
		return nil, fmt.Errorf("failed to read session id length: %w", err)
	}

	sessionID := make([]byte, sessionIDLen)
	if _, err := reader.Read(sessionID[:]); err != nil {
		return nil, fmt.Errorf("failed to read session id: %w", err)
	}

	var cipherSuitesLenBytes [2]byte
	if _, err := reader.Read(cipherSuitesLenBytes[:]); err != nil {
		return nil, fmt.Errorf("failed to read cipher suites length: %w", err)
	}

	cipherSuitesLen := binary.BigEndian.Uint16(cipherSuitesLenBytes[:])

	cipherSuitesBytes := make([]byte, cipherSuitesLen)
	if _, err := reader.Read(cipherSuitesBytes[:]); err != nil {
		return nil, fmt.Errorf("failed to read cipher suites: %w", err)
	}

	cipherSuites := make([]uint16, 0, len(cipherSuitesBytes)/2)
	for i := 0; i < len(cipherSuitesBytes); i += 2 {
		cipherSuite := binary.BigEndian.Uint16(cipherSuitesBytes[i : i+2])
		if cipherSuite&0x0f0f != 0x0a0a { // ignore any GREASE cipher suite
			cipherSuites = append(cipherSuites, cipherSuite)
		}
	}

	j.Ciphers = cipherSuites // [+]

	compressionMethodsLen, err := reader.ReadByte()
	if err != nil {
		return nil, fmt.Errorf("failed to read compression methods length: %w", err)
	}

	compressionMethods := make([]uint8, compressionMethodsLen)
	for i := 0; i < len(compressionMethods); i++ {
		if compressionMethods[i], err = reader.ReadByte(); err != nil {
			return nil, fmt.Errorf("failed to read compression method: %w", err)
		}
	}

	var extensionsLenBytes [2]byte
	if _, err := reader.Read(extensionsLenBytes[:]); err != nil {
		return nil, fmt.Errorf("failed to read extensions length: %w", err)
	}

	extensionsLen := binary.BigEndian.Uint16(extensionsLenBytes[:])

	extensionsBytes := make([]byte, extensionsLen)
	if _, err := reader.Read(extensionsBytes[:]); err != nil {
		return nil, fmt.Errorf("failed to read extensions: %w", err)
	}

	return bytes.NewReader(extensionsBytes), nil
}

/*
0x00 0x00       extension type                  uint16      [+]
0x00 0x20       extension following length      uint16
...             extension                       [N]byte
*/
func (j *JA3) readExtension(reader *bytes.Reader) error {
	for {
		var typBytes [2]byte
		if _, err := reader.Read(typBytes[:]); err != nil {
			if errors.Is(err, io.EOF) {
				break
			}
			return fmt.Errorf("failed to read extension type: %w", err)
		}

		typ := binary.BigEndian.Uint16(typBytes[:])

		if typ&0x0f0f != 0x0a0a { // ignore any GREASE extension
			j.Extensions = append(j.Extensions, typ) // [+]
		}

		var extensionLenBytes [2]byte
		if _, err := reader.Read(extensionLenBytes[:]); err != nil {
			return fmt.Errorf("failed to read extension length: %w", err)
		}

		extensionLen := binary.BigEndian.Uint16(extensionLenBytes[:])

		extensionBytes := make([]byte, extensionLen)
		if _, err := reader.Read(extensionBytes[:]); err != nil {
			return fmt.Errorf("failed to read extension: %w", err)
		}

		extensionReader := bytes.NewReader(extensionBytes)

		switch typ {
		case ellipticCurvesExtensionType:
			if err := j.readEllipticCurves(extensionReader); err != nil {
				return fmt.Errorf("failed to read elliptic curves extension: %w", err)
			}

		case ellipticCurvesPointFormatsType:
			if err := j.readEllipticCurvesPointFormats(extensionReader); err != nil {
				return fmt.Errorf("failed to read elliptic curves point formats extension: %w", err)
			}
		}
	}

	return nil
}

/*
0x00 0x20       following bytes length                  uint16
...             payload                                 [N]byte
0x00 0x10       following elliptic curves length        uint16
...             elliptic curves                         [N/2]uint16     [+]
*/
func (j *JA3) readEllipticCurves(reader *bytes.Reader) error {
	var ellipticCurvesLenBytes [2]byte
	if _, err := reader.Read(ellipticCurvesLenBytes[:]); err != nil {
		return fmt.Errorf("failed to read elliptic curves len: %w", err)
	}

	ellipticCurvesLen := binary.BigEndian.Uint16(ellipticCurvesLenBytes[:])

	ellipticCurvesBytes := make([]byte, ellipticCurvesLen)
	if _, err := reader.Read(ellipticCurvesBytes[:]); err != nil {
		return fmt.Errorf("failed to read elliptic curves: %w", err)
	}

	ellipticCurves := make([]uint16, 0, len(ellipticCurvesBytes)/2)
	for i := 0; i < len(ellipticCurvesBytes); i += 2 {
		ellipticCurve := binary.BigEndian.Uint16(ellipticCurvesBytes[i : i+2])
		if ellipticCurve&0x0f0f != 0x0a0a { // ignore any GREASE elliptic curve
			ellipticCurves = append(ellipticCurves, ellipticCurve)
		}
	}

	j.EllipticCurves = ellipticCurves // [+]

	return nil
}

/*
0x03        following bytes length      uint8
...         point formats               [N]uint8        [+]
*/
func (j *JA3) readEllipticCurvesPointFormats(reader *bytes.Reader) error {
	pointFormatsLen, err := reader.ReadByte()
	if err != nil {
		return fmt.Errorf("failed to read ec point formats len: %w", err)
	}

	pointFormats := make([]uint8, pointFormatsLen)
	if _, err := reader.Read(pointFormats[:]); err != nil {
		return fmt.Errorf("failed to read ec point formats: %w", err)
	}

	j.EllipticCurvePointFormats = pointFormats // [+]

	return nil
}

func (j *JA3) String() string {
	var sb strings.Builder

	if j.TLSVersion == 0 {
		return ""
	}

	sb.WriteString(strconv.FormatUint(uint64(j.TLSVersion), 10))
	sb.WriteString(",")

	sb.WriteString(UintSliceToString(j.Ciphers))
	sb.WriteString(",")

	sb.WriteString(UintSliceToString(j.Extensions))
	sb.WriteString(",")

	sb.WriteString(UintSliceToString(j.EllipticCurves))
	sb.WriteString(",")

	sb.WriteString(UintSliceToString(j.EllipticCurvePointFormats))

	return sb.String()
}

const (
	clientHelloType                uint8  = 0x01
	ellipticCurvesExtensionType    uint16 = 0x000a
	ellipticCurvesPointFormatsType uint16 = 0x000b
)

func UintSliceToString[T uint16 | uint8 | tls.CurveID](uintSlice []T) string {
	var sb strings.Builder
	for i, uintElement := range uintSlice {
		if i > 0 {
			sb.WriteString("-")
		}
		sb.WriteString(fmt.Sprintf("%d", uintElement))
		// sb.WriteString(fmt.Sprintf("%04x", uintElement))
	}

	return sb.String()
}
