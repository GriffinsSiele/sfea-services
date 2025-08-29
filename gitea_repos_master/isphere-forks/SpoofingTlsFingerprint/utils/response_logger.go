package utils

import (
	"bytes"
	"net/http"
)

type ResponseLogger struct {
	http.ResponseWriter
	Status int
	Buf    *bytes.Buffer
}

func (t *ResponseLogger) WriteHeader(status int) {
	t.ResponseWriter.WriteHeader(status)
}

func (t *ResponseLogger) Write(in []byte) (int, error) {
	_, _ = t.Buf.Write(in)
	return t.ResponseWriter.Write(in)
}

func NewResponseLogger(wrapped http.ResponseWriter) *ResponseLogger {
	return &ResponseLogger{
		ResponseWriter: wrapped,
		Status:         http.StatusOK,
		Buf:            bytes.NewBuffer(make([]byte, 0)),
	}
}
