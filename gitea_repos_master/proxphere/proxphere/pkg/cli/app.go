package cli

import (
	"fmt"

	"github.com/urfave/cli/v2"
	"go.uber.org/fx"

	"go.i-sphere.ru/proxy/pkg/contracts"
)

type App struct {
	*cli.App
}

func NewApp(commanders []contracts.Commander, shutdowner fx.Shutdowner) *App {
	commands := make(cli.Commands, len(commanders))
	for i, commander := range commanders {
		commands[i] = commander.NewCommand()
	}

	return &App{
		&cli.App{
			Name:     "proxphere",
			Version:  "1.2.0",
			Commands: commands,
			After: func(c *cli.Context) error {
				if err := shutdowner.Shutdown(); err != nil {
					return fmt.Errorf("shutdown error: %w", err)
				}
				if err := c.Err(); err != nil {
					return fmt.Errorf("general app failure: %w", err)
				}
				return nil
			},
		},
	}
}
