package main

import (
	"net/http"

	"git.i-sphere.ru/isphere-go-modules/dns/internal"
	"go.uber.org/fx"
)

func main() {
	fx.New(
		fx.Provide(internal.NewConfig),
		fx.Provide(internal.NewHandler),
		fx.Provide(internal.NewItemType),
		fx.Provide(internal.NewQueryType),
		fx.Provide(internal.NewResultField),
		fx.Provide(internal.NewResultResolver),
		fx.Provide(internal.NewSchema),
		fx.Provide(internal.NewServer),

		fx.Invoke(func(*http.Server) {}),
	).Run()
}
