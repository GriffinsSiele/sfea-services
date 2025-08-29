package cli

import (
	"fmt"

	"github.com/urfave/cli/v2"
	"go.uber.org/fx"
	"i-sphere.ru/kube-utils/internal/contract"
)

func NewApp(commanders []contract.Commander, shutdowner fx.Shutdowner) *cli.App {
	commands := make([]*cli.Command, len(commanders))
	for i, commander := range commanders {
		commands[i] = commander.Describe()
	}

	return &cli.App{
		Name:     "kube-utils",
		Version:  "1.0.0",
		Commands: commands,
		After: func(c *cli.Context) error {
			if err := shutdowner.Shutdown(); err != nil {
				return fmt.Errorf("failed to shutdown: %w", err)
			}
			return nil
		},
	}
}
