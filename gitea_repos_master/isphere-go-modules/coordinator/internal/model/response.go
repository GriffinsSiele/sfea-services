package model

import (
	"encoding/json"
	"fmt"
	"net/http"
	"time"

	"gitea-http.gitea.svc.cluster.local/isphere-go-modules/coordinator/internal/hacking"
	"gitea-http.gitea.svc.cluster.local/isphere-go-modules/coordinator/internal/util"
	"github.com/ake-persson/mapslice-json"
	"github.com/sirupsen/logrus"
)

type Response struct {
	Status    ResponseStatus   `json:"status" yaml:"status"`
	Code      int              `json:"code" yaml:"code"`
	Message   string           `json:"message,omitempty" yaml:"message,omitempty"`
	Records   Records          `json:"records" yaml:"records"`
	Timestamp int64            `json:"timestamp" yaml:"timestamp"`
	Metadata  ResponseMetadata `json:"-" yaml:"-"`
	Events    []*util.Event    `json:"events" yaml:"events"`
}

func NewResponseUsingRecords(records Records) *Response {
	t := &Response{
		Status:    ResponseStatusOK,
		Records:   records,
		Timestamp: time.Now().Unix(),
	}

	if len(records) > 0 {
		t.Code = ResponseCodeFound
		t.Message = "found"
	} else {
		t.Code = ResponseCodeNotFound
		t.Message = "nodata"
	}

	return t
}

func (t *Response) ToJSON() []byte {
	serialized, err := json.Marshal(t)
	if err != nil {
		logrus.WithError(err).Error("failed to serialize response")

		return nil
	}

	return serialized
}

func NewResponseUsingError(err error, errCode ResponseCode) *Response {
	return &Response{
		Status:    ResponseStatusError,
		Code:      errCode, // @todo после создания модулей проработка ошибок
		Message:   err.Error(),
		Records:   make(Records, 0),
		Timestamp: time.Now().Unix(),
	}
}

func NewResponseIncomplete() *Response {
	return &Response{
		Status:    ResponseStatusIncomplete,
		Code:      ResponseCodeIncomplete,
		Message:   "incomplete request",
		Records:   make(Records, 0),
		Timestamp: time.Now().Unix(),
	}
}

func (t *Response) IsFailed() bool {
	return t.Status == ResponseStatusError
}

func (t *Response) IsIncomplete() bool {
	return t.Status == ResponseStatusIncomplete
}

func (t *Response) IsFinite() bool {
	return !t.IsIncomplete()
}

// ---

type ResponseMetadata struct {
	TTL *ResponseMetadataTTL
}

type ResponseMetadataTTL struct {
	Age          *int
	LastModified *time.Time
	ETag         *string
	Expires      *time.Time
}

// ---

type ResponseStatus string

const (
	ResponseStatusOK         ResponseStatus = "ok"
	ResponseStatusIncomplete ResponseStatus = "incomplete"
	ResponseStatusError      ResponseStatus = "error"
)

// ---

type ResponseCode = int

const (
	ResponseCodeIncomplete ResponseCode = http.StatusAccepted
	ResponseCodeFound      ResponseCode = http.StatusOK
	ResponseCodeNotFound   ResponseCode = http.StatusNoContent
)

// ---

type Records []any

func (t *Records) UnmarshalJSON(serialized []byte) error {
	var mapSlices []mapslice.MapSlice
	if err := json.Unmarshal(serialized, &mapSlices); err != nil {
		return fmt.Errorf("failed to unmarshal records: %w", err)
	}

	records, err := hacking.CastMapSliceAsRecordsJSON(&mapSlices)
	if err != nil {
		return fmt.Errorf("failed to cast map slice as json records: %w", err)
	}

	*t = records

	return nil
}
