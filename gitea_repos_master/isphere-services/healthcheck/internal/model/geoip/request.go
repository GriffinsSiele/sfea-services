package geoip

import (
	"encoding/json"
	"time"
)

type Request struct {
	ID        int    `json:"id"`
	Key       string `json:"key"`
	IP        string `json:"ip"`
	StartTime int64  `json:"starttime"`
}

func NewRequest(ip string, requestID string) *Request {
	return &Request{
		ID:        1,
		Key:       requestID,
		IP:        ip,
		StartTime: time.Now().Unix(),
	}
}

func (r *Request) Bytes() []byte {
	b, _ := json.Marshal(r)
	return b
}
