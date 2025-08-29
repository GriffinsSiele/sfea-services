package socks5

type Command uint8

const (
	Connect   Command = 0x01
	Bind      Command = 0x02
	Associate Command = 0x03
)
