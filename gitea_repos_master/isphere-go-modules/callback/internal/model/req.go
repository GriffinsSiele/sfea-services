package model

import "time"

type Req struct {
	Method     string              `json:"method"`
	RequestURI string              `json:"request_uri"`
	Headers    map[string][]string `json:"headers"`
	Body       string              `json:"body"`
	CreatedAt  time.Time           `json:"created_at"`
}
