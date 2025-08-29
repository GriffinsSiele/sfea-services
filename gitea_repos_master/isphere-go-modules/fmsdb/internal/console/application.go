package console

import (
	"git.i-sphere.ru/isphere-go-modules/fmsdb/internal/contract"
	"github.com/urfave/cli/v2"
	"go.uber.org/fx"
)

type Application struct {
	*cli.App
}

func NewApplication(shutdowner fx.Shutdowner, commanders []contract.Commander) *Application {
	commands := make([]*cli.Command, 0, len(commanders))
	for _, commander := range commanders {
		commands = append(commands, commander.Describe())
	}

	return &Application{
		&cli.App{
			Name:     "iSphere Module FMSDB",
			Version:  "1.0.0",
			Commands: commands,
			After: func(*cli.Context) error {
				_ = shutdowner.Shutdown()

				return nil
			},
		},
	}
}
