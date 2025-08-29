package util

import (
	"net/http"
	"strings"
)

func NormalizeHeaders(headers http.Header) map[string]string {
	result := make(map[string]string)
	for k, v := range headers {
		result[k] = strings.Join(v, ", ")
	}
	return result
}
