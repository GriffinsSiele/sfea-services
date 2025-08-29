package console

import (
	"fmt"

	"git.i-sphere.ru/isphere-go-modules/grabber/internal/contract"
	"github.com/sirupsen/logrus"
	"github.com/urfave/cli/v2"
	"go.uber.org/fx"
)

func NewApplication(shutdowner fx.Shutdowner, definitions []contract.Commander) *cli.App {
	commands := make([]*cli.Command, len(definitions))
	for i, definition := range definitions {
		commands[i] = definition.Describe()
	}

	return &cli.App{
		Name:     "iSphere Grabber",
		Version:  "1.0.0",
		Commands: commands,
		ExitErrHandler: func(_ *cli.Context, err error) {
			if err != nil {
				logrus.WithError(err).Error("an error encountered while running application")
			}
		},
		After: func(c *cli.Context) error {
			if err := c.Err(); err != nil {
				return fmt.Errorf("failed to successfull exit application: %w", err)
			}

			if err := shutdowner.Shutdown(); err != nil {
				return fmt.Errorf("failed to shutdown app normally: %w", err)
			}

			return nil
		},
	}
}
