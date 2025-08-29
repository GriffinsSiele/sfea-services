package socks5

type ConnectionStatus uint8

const (
	RequestGranted          ConnectionStatus = 0x00
	GeneralFailure          ConnectionStatus = 0x01
	ConnectionNotAllowed    ConnectionStatus = 0x02
	NetworkUnreachable      ConnectionStatus = 0x03
	HostUnreachable         ConnectionStatus = 0x04
	ConnectionRefused       ConnectionStatus = 0x05
	TTLExpired              ConnectionStatus = 0x06
	CommandNotSupported     ConnectionStatus = 0x07
	AddressTypeNotSupported ConnectionStatus = 0x08
)
