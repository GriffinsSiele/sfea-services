package contract

import "context"

type Normalizer[T any] interface {
	Normalize(context.Context, T) T
	ReverseNormalize(context.Context, T) T
}
