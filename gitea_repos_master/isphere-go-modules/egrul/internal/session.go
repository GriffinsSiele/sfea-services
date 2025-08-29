package internal

import (
	"net/http"

	"github.com/google/uuid"
)

type Session struct {
	ID        uuid.UUID
	UserAgent string
	ProxyID   int
	Cookies   []*http.Cookie
	InUse     bool
}
