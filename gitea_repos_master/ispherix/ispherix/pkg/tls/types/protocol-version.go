package types

import (
	"crypto/tls"
	"fmt"
	"strconv"
)

type ProtocolVersion uint16

func (v *ProtocolVersion) String() string {
	switch *v {
	case tls.VersionTLS10:
		return "TLS 1.0"
	case tls.VersionTLS11:
		return "TLS 1.1"
	case tls.VersionTLS12:
		return "TLS 1.2"
	case tls.VersionTLS13:
		return "TLS 1.3"
	case tls.VersionSSL30:
		return "SSLv3"
	default:
		return fmt.Sprintf("0x%04x", *v)
	}
}

func (v *ProtocolVersion) MarshalJSON() ([]byte, error) {
	return []byte(strconv.Quote(v.String())), nil
}
