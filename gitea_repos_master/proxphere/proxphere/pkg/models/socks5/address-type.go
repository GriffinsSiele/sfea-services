package socks5

type AddressType uint8

const (
	IPv4   AddressType = 0x01
	IPv6   AddressType = 0x04
	Domain AddressType = 0x03
)
