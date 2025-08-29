package main

import (
	"net/http"

	"git.i-sphere.ru/isphere-go-modules/shodan/internal"
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
		fx.Provide(internal.NewClient),
		fx.Provide(internal.NewConfig),
		fx.Provide(internal.NewHandler),
		fx.Provide(internal.NewIPItemType),
		fx.Provide(internal.NewItemType),
		fx.Provide(internal.NewQueryType),
		fx.Provide(internal.NewResultField),
		fx.Provide(internal.NewResultResolver),
		fx.Provide(internal.NewSchema),
		fx.Provide(internal.NewServer),
		fx.Provide(internal.NewServiceItemType),
		fx.Provide(internal.NewShodan),
	}
}
