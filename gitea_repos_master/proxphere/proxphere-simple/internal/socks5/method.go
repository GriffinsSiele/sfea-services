package socks5

type Method uint8

const (
	MethodNoAuthentication Method = 0x00
	MethodGSSAPI           Method = 0x01
	MethodUsernamePassword Method = 0x02
	MethodUnsupported      Method = 0xff
)
