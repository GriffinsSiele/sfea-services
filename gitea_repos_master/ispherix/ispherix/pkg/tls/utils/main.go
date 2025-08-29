package utils

func NotGrease(v uint16) bool {
	return v&0x0f0f != 0x0a0a
}
