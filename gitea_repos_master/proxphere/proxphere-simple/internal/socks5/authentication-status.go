package socks5

type AuthenticationStatus uint8

const (
	AuthenticationStatusSuccess AuthenticationStatus = 0x00
	AuthenticationStatusFailed  AuthenticationStatus = 0xff
)
