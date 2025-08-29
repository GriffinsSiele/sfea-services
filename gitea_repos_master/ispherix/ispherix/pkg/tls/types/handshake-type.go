package types

import (
	"fmt"
	"strconv"
)

type HandshakeType uint8

const (
	HandshakeTypeHelloRequest       HandshakeType = 0
	HandshakeTypeClientHello        HandshakeType = 1
	HandshakeTypeServerHello        HandshakeType = 2
	HandshakeTypeCertificate        HandshakeType = 11
	HandshakeTypeServerKeyExchange  HandshakeType = 12
	HandshakeTypeCertificateRequest HandshakeType = 13
	HandshakeTypeServerHelloDone    HandshakeType = 14
	HandshakeTypeCertificateVerify  HandshakeType = 15
	HandshakeTypeClientKeyExchange  HandshakeType = 16
	HandshakeTypeFinished           HandshakeType = 20
)

func (t *HandshakeType) String() string {
	switch *t {
	case HandshakeTypeHelloRequest:
		return "hello_request"
	case HandshakeTypeClientHello:
		return "client_hello"
	case HandshakeTypeServerHello:
		return "server_hello"
	case HandshakeTypeCertificate:
		return "certificate"
	case HandshakeTypeServerKeyExchange:
		return "server_key_exchange"
	case HandshakeTypeCertificateRequest:
		return "certificate_request"
	case HandshakeTypeServerHelloDone:
		return "server_hello_done"
	case HandshakeTypeCertificateVerify:
		return "certificate_verify"
	case HandshakeTypeClientKeyExchange:
		return "client_key_exchange"
	case HandshakeTypeFinished:
		return "finished"
	default:
		return fmt.Sprintf("0x%02x", *t)
	}
}

func (t *HandshakeType) MarshalJSON() ([]byte, error) {
	return []byte(strconv.Quote(t.String())), nil
}
