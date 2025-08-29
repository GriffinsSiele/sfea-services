package util

func WithDefault(in []byte, def []byte) []byte {
	if len(in) > 0 {
		return in
	}

	return def
}

func WrapBytes(body []byte, delim ...[]byte) []byte {
	if len(delim) == 0 {
		return body
	}

	var (
		prefix = delim[0]
		suffix []byte
	)

	if len(delim) == 1 {
		suffix = prefix
	} else {
		suffix = delim[1]
	}

	result := append(prefix, body...)

	result = append(result, suffix...)

	return result
}
