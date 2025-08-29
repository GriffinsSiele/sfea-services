package console

import (
	"fmt"

	"git.i-sphere.ru/isphere-go-modules/framework/pkg/contract"
	"git.i-sphere.ru/isphere-go-modules/framework/pkg/runtime"
	"github.com/urfave/cli/v2"
	"go.uber.org/fx"
)

type Application struct {
	*cli.App
}

func NewApplication(shutdowner fx.Shutdowner, commanders []contract.Commander) *Application {
	commands := make(cli.CommandsByName, 0, len(commanders))
	for _, commander := range commanders {
		commands = append(commands, commander.Command())
	}

	return &Application{
		&cli.App{
			Name:     runtime.String("APPLICATION_NAME"),
			Version:  runtime.String("APPLICATION_VERSION"),
			Commands: commands,
			After: func(ctx *cli.Context) error {
				if err := shutdowner.Shutdown(); err != nil {
					return fmt.Errorf("it was not possible to shutdown the container normally: %w", err)
				}

				return nil
			},
		},
	}
}
