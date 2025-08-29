package util

import "encoding/json"

func MustMarshal(v any) []byte {
	b, _ := json.Marshal(v)
	return b
}

func MustUnmarshal(b []byte, v any) {
	_ = json.Unmarshal(b, v)
}

func MustErrString(err error) string {
	if err == nil {
		return ""
	}
	return err.Error()
}
