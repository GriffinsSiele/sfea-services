package app

import (
	"context"
	"fmt"
	"os"

	"github.com/sirupsen/logrus"
	"github.com/urfave/cli/v2"
	"go.uber.org/fx"
)

func NewApp(lifecycle fx.Lifecycle, shutdowner fx.Shutdowner, commands []*cli.Command) *cli.App {
	app := &cli.App{
		Name:     "client",
		Commands: commands,

		After: func(c *cli.Context) error {
			if err := c.Err(); err != nil {
				return fmt.Errorf("failed to successful exit application: %w", err)
			}

			if err := shutdowner.Shutdown(); err != nil {
				return fmt.Errorf("failed to shutdown app normally: %w", err)
			}

			return nil
		},

		ExitErrHandler: func(_ *cli.Context, err error) {
			if err != nil {
				logrus.WithError(err).Error("an error encountered while running application")
			}
		},
	}

	var cancel context.CancelFunc

	lifecycle.Append(fx.Hook{
		OnStart: func(ctx context.Context) error {
			ctx, cancel = context.WithCancel(ctx)

			go func() {
				if err := app.RunContext(ctx, os.Args); err != nil {
					logrus.WithError(err).Error("failed to run application")
				}
			}()

			return nil
		},

		OnStop: func(ctx context.Context) error {
			cancel()

			return nil
		},
	})

	return app
}
