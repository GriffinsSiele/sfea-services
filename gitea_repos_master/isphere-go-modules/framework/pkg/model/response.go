package model

import (
	"encoding/json"
	"time"
)

type Response struct {
	Status    string         `json:"status"`
	Code      ResponseCode   `json:"code"`
	Message   string         `json:"message,omitempty"`
	Records   [][]Recorder   `json:"records"`
	SessionID string         `json:"sessionId,omitempty"`
	Timestamp int64          `json:"timestamp"`
	TTL       *time.Duration `json:"-"`
}

func NewResponse(records ...[]Recorder) *Response {
	response := &Response{
		Status:    "ok",
		Records:   records,
		Timestamp: time.Now().Unix(),
	}

	if len(records) > 0 {
		response.Code = ResponseCodeFoundAtLeastOneRecord
	} else {
		response.Code = ResponseCodeNotFound
	}

	return response
}

func NewErrorResponse(err error) *Response {
	return &Response{
		Status:    err.Error(),
		Code:      ResponseCodeError,
		Message:   err.Error(),
		Timestamp: time.Now().Unix(),
	}
}

func (t *Response) IsCacheable() bool {
	return t.Code == ResponseCodeFoundAtLeastOneRecord || t.Code == ResponseCodeNotFound
}

// ---

type ResponseCode uint16

const (
	ResponseCodeFoundAtLeastOneRecord ResponseCode = 200
	ResponseCodeNotFound              ResponseCode = 204
	ResponseCodeError                 ResponseCode = 500
)

func (t ResponseCode) MarshalBinary() ([]byte, error) {
	return json.Marshal(t)
}
