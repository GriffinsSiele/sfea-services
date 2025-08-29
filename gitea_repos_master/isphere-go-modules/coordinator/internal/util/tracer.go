package util

import (
	"github.com/opentracing/opentracing-go"
	"github.com/opentracing/opentracing-go/log"
)

func Fail(span opentracing.Span, err error) error {
	span.SetTag("error", true)
	span.LogFields(log.String("error", err.Error()))
	return err
}
