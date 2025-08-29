package tags

import (
	"bytes"
	"context"
	"runtime"
	"strconv"
)

type contextType struct {
	string
}

var (
	goroutineIDContextValue = &contextType{"goroutineID"}
	scopeContextValue       = &contextType{"scope"}
)

func WithGoroutineID(ctx context.Context) context.Context {
	buf := make([]byte, 32)
	n := runtime.Stack(buf, false)
	buf = buf[:n]

	buf, ok := bytes.CutPrefix(buf, []byte("goroutine "))
	if !ok {
		return ctx
	}

	i := bytes.IndexByte(buf, ' ')
	if i < 0 {
		return ctx
	}

	goid, err := strconv.Atoi(string(buf[:i]))
	if err != nil {
		return ctx
	}

	return context.WithValue(ctx, goroutineIDContextValue, goid)
}

func GetGoroutineID(ctx context.Context) (int, bool) {
	goid, ok := ctx.Value(goroutineIDContextValue).(int)
	return goid, ok
}

func WithScope(ctx context.Context, scope string) context.Context {
	return context.WithValue(ctx, scopeContextValue, scope)
}

func GetScope(ctx context.Context) (string, bool) {
	scope, ok := ctx.Value(scopeContextValue).(string)
	return scope, ok
}
