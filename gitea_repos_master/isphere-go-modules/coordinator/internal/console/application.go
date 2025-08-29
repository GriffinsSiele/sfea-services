package console

import (
	"fmt"

	"gitea-http.gitea.svc.cluster.local/isphere-go-modules/coordinator/internal/contract"
	"github.com/sirupsen/logrus"
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
			Name:     "iSphere Coordinator",
			Version:  "1.0.0",
			Commands: commands,
			Flags: cli.FlagsByName{
				&cli.BoolFlag{
					Name: "verbose",
				},
			},
			ExitErrHandler: func(_ *cli.Context, err error) {
				if err != nil {
					logrus.WithError(err).Error("an error encountered while running application")
				}
			},
			Before: func(ctx *cli.Context) error {
				fmt.Println("iSphere Coordinator v1.0.0")
				fmt.Println()
				return nil
			},
			After: func(c *cli.Context) error {
				if err := c.Err(); err != nil {
					return fmt.Errorf("failed to successful exit application: %w", err)
				}

				if err := shutdowner.Shutdown(); err != nil {
					return fmt.Errorf("failed to shutdown app normally: %w", err)
				}

				return nil
			},
		},
	}
}
