package contracts

import "go.uber.org/fx"

func AsOne[T any](t any, tags string) any {
	return fx.Annotate(t,
		fx.As(new(T)),
		fx.ResultTags(tags),
	)
}

func WithMany[T any](tags string) any {
	return fx.Annotate(
		func(t []T) []T { return t },
		fx.ParamTags(tags),
	)
}
