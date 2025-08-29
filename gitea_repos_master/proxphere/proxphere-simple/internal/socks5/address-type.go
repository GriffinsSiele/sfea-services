package socks5

type AddressType uint8

const (
	AddressIPv4   AddressType = 0x01
	AddressIPv6   AddressType = 0x04
	AddressDomain AddressType = 0x03
)
