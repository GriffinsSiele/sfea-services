package tcp

import (
	"fmt"
	"net/http"
)

type MemoryRecorder struct {
	http.ResponseWriter
	StatusCode int
	BodySize   int
}

func NewWithResponseWriter(respWriter http.ResponseWriter) *MemoryRecorder {
	return &MemoryRecorder{
		ResponseWriter: respWriter,
	}
}

func (r *MemoryRecorder) WriteHeader(statusCode int) {
	r.StatusCode = statusCode
	r.ResponseWriter.WriteHeader(statusCode)
}

func (r *MemoryRecorder) Write(data []byte) (int, error) {
	n, err := r.ResponseWriter.Write(data)
	if err != nil {
		return 0, fmt.Errorf("failed to write response: %w", err)
	}
	r.BodySize += n
	return n, nil
}
