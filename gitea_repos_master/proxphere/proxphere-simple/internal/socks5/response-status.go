package socks5

type ResponseStatus uint8

const (
	ResponseStatusGranted                 ResponseStatus = 0x00
	ResponseStatusGeneralFailure          ResponseStatus = 0x01
	ResponseStatusConnectionNotAllowed    ResponseStatus = 0x02
	ResponseStatusNetworkUnreachable      ResponseStatus = 0x03
	ResponseStatusHostUnreachable         ResponseStatus = 0x04
	ResponseStatusConnectionRefused       ResponseStatus = 0x05
	ResponseStatusTTLExpired              ResponseStatus = 0x06
	ResponseStatusCommandNotSupported     ResponseStatus = 0x07
	ResponseStatusAddressTypeNotSupported ResponseStatus = 0x08
)
