package x

import (
	"bytes"
	"encoding/binary"
	"encoding/hex"
	"errors"
	"fmt"
	"io"
	"sort"
	"strings"
)

type TLSPlaintext struct {
	ContentType     ContentType
	ProtocolVersion TLSVersion
	Opaque          *Handshake
}

func NewTLSPlaintextWithReader(reader *bytes.Reader) (*TLSPlaintext, error) {
	p := new(TLSPlaintext)

	// ContentType
	for {
		if contentType, err := reader.ReadByte(); err != nil {
			return nil, fmt.Errorf("failed to read type: %w", err)
		} else {
			p.ContentType = ContentType(contentType)
		}
		if p.ContentType != 0x00 {
			break
		}
	}

	// ClientVersion
	var recordVersionBytes [2]byte
	if _, err := reader.Read(recordVersionBytes[:]); err != nil {
		return nil, fmt.Errorf("failed to read record version: %w", err)
	}

	p.ProtocolVersion = TLSVersion(binary.BigEndian.Uint16(recordVersionBytes[:]))

	// Length
	var lengthBytes [2]byte
	if _, err := reader.Read(lengthBytes[:]); err != nil {
		return nil, fmt.Errorf("failed to read length: %w", err)
	}

	// Opaque
	if opaque, err := NewHandshakeWithReader(reader); err != nil {
		return nil, fmt.Errorf("failed to create handshake message: %w", err)
	} else {
		p.Opaque = opaque
	}

	return p, nil
}

func (p *TLSPlaintext) JsonStruct() map[string]any {
	m := map[string]any{
		"content_type":     p.ContentType.String(),
		"protocol_version": p.ProtocolVersion.String(),
		"ja3":              p.JA3(),
	}

	withGrease := func(v string) string { return "grease:" + v }
	withGreaseC := func(v string, ok bool) string {
		if ok {
			return withGrease(v)
		} else {
			return v
		}
	}

	if h := p.Opaque; h != nil {
		m["msg_type"] = p.Opaque.MsgType.String()

		if cl := h.Body; cl != nil {
			m["client_version"] = cl.ClientVersion.String()
			m["random"] = hex.EncodeToString(cl.Random[:])
			m["session_id"] = hex.EncodeToString(cl.SessionID)

			m["cipher_suites"] = make([]string, len(cl.CipherSuites))
			for i, cipherSuite := range cl.CipherSuites {
				m["cipher_suites"].([]string)[i] = withGreaseC(cipherSuite.String(), cipherSuite.IsGreased())
			}

			m["compression_methods"] = make([]string, len(cl.CompressionMethods))
			for i, compressionMethod := range cl.CompressionMethods {
				m["compression_methods"].([]string)[i] = compressionMethod.String()
			}

			m["extensions"] = make([]map[string]any, len(cl.Extensions))

			for i, extension := range cl.GetExtensionsOrdered() {
				e := map[string]any{
					"extension_type": withGreaseC(extension.ExtensionType.String(), extension.ExtensionType.IsGreased()),
				}

				switch val := extension.Opaque.(type) {
				case []*ServerName:
					list := make([]map[string]any, len(val))
					for j, v := range val {
						list[j] = map[string]any{
							"name_type": v.NameType.String(),
							"name":      v.Name,
						}
					}
					e["server_name_list"] = list

				case []SupportedGroup:
					list := make([]string, len(val))
					for j, v := range val {
						list[j] = withGreaseC(v.String(), v.IsGreased())
					}
					e["supported_groups"] = list

				case []ECPointFormat:
					list := make([]string, len(val))
					for j, v := range val {
						list[j] = v.String()
					}
					e["ec_point_formats"] = list
				}

				m["extensions"].([]map[string]any)[i] = e
			}
		}
	}

	ja3 := p.JA3()
	if ja3 != ",,,," {
		m["ja3"] = ja3
		m["ja3_hash"] = MD5Hash(ja3)
	}

	return m
}

// JA3 https://github.com/salesforce/ja3
func (p *TLSPlaintext) JA3() string {
	var sb strings.Builder

	// SSLVersion
	if o := p.Opaque; o != nil {
		if b := o.Body; b != nil {
			sb.WriteString(fmt.Sprintf("%d", b.ClientVersion))
		}
	}
	sb.WriteRune(',')

	// Cipher
	if o := p.Opaque; o != nil {
		if b := o.Body; b != nil {
			var j int
			for _, cipherSuite := range b.CipherSuites {
				if cipherSuite.IsGreased() {
					continue
				}
				if j > 0 {
					sb.WriteRune('-')
				}
				sb.WriteString(fmt.Sprintf("%d", cipherSuite))
				j++
			}
		}
	}
	sb.WriteRune(',')

	// SSLExtension
	if o := p.Opaque; o != nil {
		if b := o.Body; b != nil {
			var j int
			for _, extension := range b.GetExtensionsOrdered() {
				if extension.ExtensionType.IsGreased() || extension.ExtensionType == ExtensionTypePadding {
					continue
				}
				if j > 0 {
					sb.WriteRune('-')
				}
				sb.WriteString(fmt.Sprintf("%d", extension.ExtensionType))
				j++
			}
		}
	}
	sb.WriteString(",")

	// EllipticCurve
	if o := p.Opaque; o != nil {
		if b := o.Body; b != nil {
			if g := b.Extension(ExtensionTypeSupportedGroups); g != nil {
				var j int
				for _, group := range g.Opaque.([]SupportedGroup) {
					if group.IsGreased() {
						continue
					}
					if j > 0 {
						sb.WriteRune('-')
					}
					sb.WriteString(fmt.Sprintf("%d", group))
					j++
				}
			}
		}
	}
	sb.WriteString(",")

	// EllipticCurvePointFormat
	if o := p.Opaque; o != nil {
		if b := o.Body; b != nil {
			if g := b.Extension(ExtensionTypeECPointFormats); g != nil {
				for i, pointFormat := range g.Opaque.([]ECPointFormat) {
					if i > 0 {
						sb.WriteRune('-')
					}
					sb.WriteString(fmt.Sprintf("%d", pointFormat))
				}
			}
		}
	}

	return sb.String()
}

type Handshake struct {
	MsgType HandshakeType
	Body    *ClientHello
}

func NewHandshakeWithReader(reader *bytes.Reader) (*Handshake, error) {
	m := new(Handshake)

	// ContentType
	if typ, err := reader.ReadByte(); err != nil {
		return nil, fmt.Errorf("failed to read type: %w", err)
	} else {
		m.MsgType = HandshakeType(typ)
	}

	// Length
	var lengthBytes [3]byte
	if _, err := reader.Read(lengthBytes[:]); err != nil {
		return nil, fmt.Errorf("failed to read length: %w", err)
	}

	// Body
	if body, err := NewClientHelloWithReader(reader); err != nil {
		return nil, fmt.Errorf("failed to create client hello: %w", err)
	} else {
		m.Body = body
	}

	return m, nil
}

type ClientHello struct {
	ClientVersion      TLSVersion
	Random             [32]byte
	SessionID          []byte
	CipherSuites       []CipherSuite
	CompressionMethods []CompressionMethod
	Extensions         []*Extension
}

func NewClientHelloWithReader(reader *bytes.Reader) (*ClientHello, error) {
	h := new(ClientHello)

	// ClientVersion
	var versionBytes [2]byte
	if _, err := reader.Read(versionBytes[:]); err != nil {
		return nil, fmt.Errorf("failed to read version: %w", err)
	}

	h.ClientVersion = TLSVersion(binary.BigEndian.Uint16(versionBytes[:]))

	// Random
	if _, err := reader.Read(h.Random[:]); err != nil {
		return nil, fmt.Errorf("failed to read random: %w", err)
	}

	// SessionID
	sessionIDLength, err := reader.ReadByte()
	if err != nil {
		return nil, fmt.Errorf("failed to read session id length: %w", err)
	}

	h.SessionID = make([]byte, sessionIDLength)
	if _, err = reader.Read(h.SessionID); err != nil {
		return nil, fmt.Errorf("failed to read session id: %w", err)
	}

	// CipherSuites
	var cipherSuitesLengthBytes [2]byte
	if _, err = reader.Read(cipherSuitesLengthBytes[:]); err != nil {
		return nil, fmt.Errorf("failed to read cipher suites length: %w", err)
	}

	cipherSuiteLength := binary.BigEndian.Uint16(cipherSuitesLengthBytes[:])

	h.CipherSuites = make([]CipherSuite, 0, cipherSuiteLength/2)

	for i, l := 0, int(cipherSuiteLength)/2; i < l; i++ {
		var cipherSuiteBytes [2]byte
		if _, err = reader.Read(cipherSuiteBytes[:]); err != nil {
			return nil, fmt.Errorf("failed to read cipher suite: %w", err)
		}

		h.CipherSuites = append(h.CipherSuites, CipherSuite(binary.BigEndian.Uint16(cipherSuiteBytes[:])))
	}

	// CompressionMethods
	compressionMethodsLength, err := reader.ReadByte()
	if err != nil {
		return nil, fmt.Errorf("failed to read compression methods length: %w", err)
	}

	compressionMethods := make([]uint8, compressionMethodsLength)
	if _, err = reader.Read(compressionMethods); err != nil {
		return nil, fmt.Errorf("failed to read compression methods: %w", err)
	}

	h.CompressionMethods = make([]CompressionMethod, len(compressionMethods))
	for i, m := range compressionMethods {
		h.CompressionMethods[i] = CompressionMethod(m)
	}

	// Extensions
	var extensionsLengthBytes [2]byte
	if _, err = reader.Read(extensionsLengthBytes[:]); err != nil {
		return nil, fmt.Errorf("failed to read extensions length: %w", err)
	}

	extensionsBytes := make([]byte, binary.BigEndian.Uint16(extensionsLengthBytes[:]))
	if _, err = reader.Read(extensionsBytes); err != nil {
		return nil, fmt.Errorf("failed to read extensions: %w", err)
	}

	extensionsReader := bytes.NewReader(extensionsBytes)

	h.Extensions = make([]*Extension, 0)

	for {
		extension, err := NewExtensionWithReader(extensionsReader)
		if err != nil {
			if errors.Is(err, io.EOF) {
				break
			}

			return nil, fmt.Errorf("failed to read extension: %w", err)
		}

		h.Extensions = append(h.Extensions, extension)
	}

	return h, nil
}

func (h *ClientHello) GetExtensionsOrdered() []*Extension {
	extensions := make([]*Extension, len(h.Extensions))
	copy(extensions, h.Extensions)
	sort.Slice(extensions, func(i, j int) bool {
		return extensions[i].ExtensionType < extensions[j].ExtensionType
	})
	return extensions
}

func (h *ClientHello) Extension(extensionType ExtensionType) *Extension {
	for _, extension := range h.Extensions {
		if extension.ExtensionType == extensionType {
			return extension
		}
	}
	return nil
}

type Extension struct {
	ExtensionType ExtensionType
	Opaque        any
}

func NewExtensionWithReader(reader *bytes.Reader) (*Extension, error) {
	e := new(Extension)

	var extensionTypeBytes [2]byte
	if _, err := reader.Read(extensionTypeBytes[:]); err != nil {
		return nil, fmt.Errorf("failed to read extension type: %w", err)
	}

	e.ExtensionType = ExtensionType(binary.BigEndian.Uint16(extensionTypeBytes[:]))

	var lengthBytes [2]byte
	if _, err := reader.Read(lengthBytes[:]); err != nil {
		return nil, fmt.Errorf("failed to read length: %w", err)
	}

	length := binary.BigEndian.Uint16(lengthBytes[:])

	extension := make([]byte, length)
	if _, err := reader.Read(extension); err != nil {
		return nil, fmt.Errorf("failed to read extension: %w", err)
	}

	switch e.ExtensionType {
	case ExtensionTypeServerName:
		if opaque, err := NewServerNameListWithReader(bytes.NewReader(extension)); err != nil {
			return nil, fmt.Errorf("failed to create server name: %w", err)
		} else {
			e.Opaque = opaque
		}
	case ExtensionTypeSupportedGroups:
		if opaque, err := NewSupportedGroupListWithReader(bytes.NewReader(extension)); err != nil {
			return nil, fmt.Errorf("failed to create supported groups: %w", err)
		} else {
			e.Opaque = opaque
		}
	case ExtensionTypeECPointFormats:
		if opaque, err := NewECPointFormatListWithReader(bytes.NewReader(extension)); err != nil {
			return nil, fmt.Errorf("failed to create ec point formats: %w", err)
		} else {
			e.Opaque = opaque
		}
	default:
		e.Opaque = nil
	}

	return e, nil
}

type ServerName struct {
	NameType ServerNameType
	Name     string
}

// https://datatracker.ietf.org/doc/html/rfc6066#section-3
func NewServerNameListWithReader(reader *bytes.Reader) ([]*ServerName, error) {
	var entryLengthBytes [2]byte
	if _, err := reader.Read(entryLengthBytes[:]); err != nil {
		return nil, fmt.Errorf("failed to read entry length: %w", err)
	}

	entry := make([]byte, binary.BigEndian.Uint16(entryLengthBytes[:]))
	if _, err := reader.Read(entry); err != nil {
		return nil, fmt.Errorf("failed to read entry: %w", err)
	}

	entryReader := bytes.NewReader(entry)

	s := new(ServerName)

	// NameType
	if nameType, err := entryReader.ReadByte(); err != nil {
		return nil, fmt.Errorf("failed to read host type: %w", err)
	} else {
		s.NameType = ServerNameType(nameType)
	}

	// HostName
	var hostnameLengthBytes [2]byte
	if _, err := entryReader.Read(hostnameLengthBytes[:]); err != nil {
		return nil, fmt.Errorf("failed to read hostname length: %w", err)
	}

	hostnameBytes := make([]byte, binary.BigEndian.Uint16(hostnameLengthBytes[:]))
	if _, err := entryReader.Read(hostnameBytes); err != nil {
		return nil, fmt.Errorf("failed to read hostname: %w", err)
	} else {
		s.Name = string(hostnameBytes)
	}

	return []*ServerName{s}, nil
}

func NewSupportedGroupListWithReader(reader *bytes.Reader) ([]SupportedGroup, error) {
	var lengthBytes [2]byte
	if _, err := reader.Read(lengthBytes[:]); err != nil {
		return nil, fmt.Errorf("failed to read length: %w", err)
	}

	list := make([]SupportedGroup, binary.BigEndian.Uint16(lengthBytes[:])/2)

	for i := 0; i < len(list); i++ {
		var supportedGroupBytes [2]byte
		if _, err := reader.Read(supportedGroupBytes[:]); err != nil {
			return nil, fmt.Errorf("failed to read supported group: %w", err)
		}

		list[i] = SupportedGroup(binary.BigEndian.Uint16(supportedGroupBytes[:]))
	}

	return list, nil
}

func NewECPointFormatListWithReader(reader *bytes.Reader) ([]ECPointFormat, error) {
	length, err := reader.ReadByte()
	if err != nil {
		return nil, fmt.Errorf("failed to read length: %w", err)
	}

	list := make([]ECPointFormat, length)

	for i := 0; i < len(list); i++ {
		if ecPointFormat, err := reader.ReadByte(); err != nil {
			return nil, fmt.Errorf("failed to read ec point format: %w", err)
		} else {
			list[i] = ECPointFormat(ecPointFormat)
		}
	}

	return list, nil
}
