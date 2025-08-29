package main

import (
	"context"
	"os"

	"git.i-sphere.ru/grabber/internal"
	"git.i-sphere.ru/grabber/internal/cli"
	"git.i-sphere.ru/grabber/internal/commands"
	"git.i-sphere.ru/grabber/internal/contracts"
	"git.i-sphere.ru/grabber/internal/crawler"
	"git.i-sphere.ru/grabber/internal/grabbers/fns"
	cliv2 "github.com/urfave/cli/v2"
	"go.uber.org/fx"
)

func main() {
	fx.New(fx.Module("grabber",
		fx.Provide(
			cli.NewParams,
			contracts.AsCommander(commands.NewDownload),
			contracts.AsGrabber(fns.NewDebtam),
			contracts.WithCommanders(),
			contracts.WithGrabbers(),
			crawler.NewService,
			internal.NewApp,
		),

		fx.Invoke(func(app *cliv2.App) error {
			return app.RunContext(context.Background(), os.Args)
		}),
	)).Run()
}
