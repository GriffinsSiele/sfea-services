package models

import (
	"time"

	"github.com/google/uuid"
)

type ProxySpecLog struct {
	ID                 uuid.UUID
	ProxySpecID        int
	RequestHost        string
	Duration           *time.Duration
	ResponseStatusCode *int
	Error              *string
	Master             bool
	CreatedAt          *time.Time
}

func NewProxySpecLog() *ProxySpecLog {
	createdAt := time.Now()

	return &ProxySpecLog{
		ID:        uuid.New(),
		CreatedAt: &createdAt,
	}
}
