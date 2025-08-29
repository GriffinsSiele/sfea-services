package socks5

type ServerResponse struct {
	Version uint8
	Status  uint8
}

func (t *ServerResponse) MarshalBytes() ([]byte, error) {
	return []byte{
		t.Version,
		t.Status,
		0x00,
		0x01,
		0x00, 0x00, 0x00, 0x00,
		0x00, 0x00,
	}, nil
}
