package util

import (
	"context"
)

type Context struct {
	context.Context

	SpecialHeaders SpecialHeaders
	Host           string
	ProxyID        int
	ProxyGroup     int
	RegionCode     string
	RequestID      string
	UberTraceID    string
}

func NewContext(ctx context.Context) *Context {
	return &Context{
		Context: ctx,

		SpecialHeaders: make(SpecialHeaders),
	}
}

type SpecialHeaders map[string][]string

func (t *SpecialHeaders) Add(key, value string) {
	if _, ok := (*t)[key]; !ok {
		(*t)[key] = make([]string, 0)
	}
	(*t)[key] = append((*t)[key], value)
}
