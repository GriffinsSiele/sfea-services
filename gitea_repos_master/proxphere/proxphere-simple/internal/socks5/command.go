package socks5

type Command uint8

const (
	CommandConnect      Command = 0x01
	CommandBind         Command = 0x02
	CommandAssociateUDP Command = 0x03
)
