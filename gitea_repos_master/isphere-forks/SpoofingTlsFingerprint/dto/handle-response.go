package dto

import "github.com/Danny-Dasilva/CycleTLS/cycletls"

type HandleResponse struct {
	Success bool                   `json:"success"`
	Error   string                 `json:"error"`
	Payload *HandleResponsePayload `json:"payload"`
}

type HandleResponsePayload struct {
	Text       string              `json:"text"`
	Headers    map[string]string   `json:"headers"`
	HeadersISO map[string][]string `json:"headers_iso"`
	Status     int                 `json:"status"`
	Url        string              `json:"url"`
	Cookies    []*cycletls.Cookie  `json:"cookies"`
}
