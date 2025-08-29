package contract

import "context"

const ExtraRequestID string = "X-Request-Id"

type Extra string

const ExtraRequestIDCtxValue Extra = "X-Request-Id"

func WithRequestID(ctx context.Context, requestID string) context.Context {
	return context.WithValue(ctx, ExtraRequestIDCtxValue, requestID)
}
