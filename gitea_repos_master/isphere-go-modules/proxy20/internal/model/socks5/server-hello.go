package socks5

type ServerHello struct {
	Version              uint8
	AuthenticationMethod uint8
}

func (t *ServerHello) MarshalBytes() ([]byte, error) {
	return []byte{
		t.Version,
		t.AuthenticationMethod,
	}, nil
}
