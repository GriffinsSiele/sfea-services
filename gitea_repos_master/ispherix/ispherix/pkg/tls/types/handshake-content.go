package types

type HandshakeContent interface {
	Parse([]byte) error
}
