package main

import (
	"net/http"

	"git.i-sphere.ru/isphere-go-modules/d0o/internal"
	"go.uber.org/fx"
)

func main() {
	opts := append(
		options(),
		fx.Invoke(func(*http.Server) {}),
	)

	fx.New(opts...).Run()
}

func options() []fx.Option {
	return []fx.Option{
		fx.Provide(internal.NewAdjacentField),
		fx.Provide(internal.NewAdjacentResolver),
		fx.Provide(internal.NewAdjacentType),
		fx.Provide(internal.NewAdsField),
		fx.Provide(internal.NewAdsResolver),
		fx.Provide(internal.NewAdType),
		fx.Provide(internal.NewClient),
		fx.Provide(internal.NewConfig),
		fx.Provide(internal.NewD0O),
		fx.Provide(internal.NewHandler),
		fx.Provide(internal.NewNamesField),
		fx.Provide(internal.NewNamesResolver),
		fx.Provide(internal.NewNameType),
		fx.Provide(internal.NewQueryType),
		fx.Provide(internal.NewSchema),
		fx.Provide(internal.NewServer),
		fx.Provide(internal.NewTelType),
	}
}
