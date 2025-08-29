package socks5

type NegotiationStatus uint8

const (
	NegotiationSuccess NegotiationStatus = 0x00
	NegotiationFailed  NegotiationStatus = 0x01
)
