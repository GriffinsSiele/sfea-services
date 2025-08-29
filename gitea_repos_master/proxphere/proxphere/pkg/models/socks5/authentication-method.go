package socks5

type AuthenticationMethod uint8

const (
	NoAuthentication AuthenticationMethod = 0x00
	GSSAPI           AuthenticationMethod = 0x01
	UsernamePassword AuthenticationMethod = 0x02
	NoAcceptable     AuthenticationMethod = 0xFF
)
