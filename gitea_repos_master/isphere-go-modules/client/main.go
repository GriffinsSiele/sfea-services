package main

import (
	"github.com/sirupsen/logrus"
	"github.com/urfave/cli/v2"
	"go.uber.org/fx"

	"git.i-sphere.ru/client/internal/components/app"
	"git.i-sphere.ru/client/internal/components/app/commands"
	"git.i-sphere.ru/client/internal/components/app/controllers"
	"git.i-sphere.ru/client/internal/components/app/graphql"
	"git.i-sphere.ru/client/internal/components/app/queries"
	"git.i-sphere.ru/client/internal/contracts"
)

func main() {
	logrus.SetLevel(logrus.DebugLevel)

	fx.New(
		fx.WithLogger(app.NewLogger),
		fx.Provide(
			app.NewApp,
			app.NewEncoder,
			app.NewEnv,
			contracts.AsCommander(commands.NewHTTPServe),
			contracts.AsController(controllers.NewGraphQL),
			contracts.AsController(controllers.NewHealth),
			contracts.AsQuerier(queries.NewHealth),
			fx.Annotate(app.NewCommands, fx.ParamTags(contracts.CommanderTag)),
			fx.Annotate(app.NewRouter, fx.ParamTags(contracts.ControllerTag)),
			fx.Annotate(graphql.NewRootQuery, fx.ParamTags(contracts.QuerierTag)),
			graphql.NewObserver,
			graphql.NewSchema,
		),
		fx.Invoke(func(*cli.App) {}),
	).Run()
}
