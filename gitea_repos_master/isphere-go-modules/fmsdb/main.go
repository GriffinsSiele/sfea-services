package main

import (
	"context"
	"fmt"
	"os"

	"git.i-sphere.ru/isphere-go-modules/fmsdb/internal/client"
	"git.i-sphere.ru/isphere-go-modules/fmsdb/internal/command"
	"git.i-sphere.ru/isphere-go-modules/fmsdb/internal/console"
	"git.i-sphere.ru/isphere-go-modules/fmsdb/internal/contract"
	"git.i-sphere.ru/isphere-go-modules/fmsdb/internal/service"
	"github.com/getsentry/sentry-go"
	"github.com/joho/godotenv"
	"github.com/sirupsen/logrus"
	"go.uber.org/fx"
)

func main() {
	if err := env(); err != nil {
		logrus.WithError(err).Fatal("env")
	}

	_ = godotenv.Overload(".env.local")

	fx.New(
		fx.Provide(client.NewS3),
		fx.Provide(command.NewBuildIndexCommand),
		fx.Provide(command.NewHTTPServeCommand),
		fx.Provide(command.NewMigrateCommand),
		fx.Provide(command.NewUnarchiveCommand),
		fx.Provide(command.NewUpdateSourceCommand),
		fx.Provide(console.NewApplication),
		fx.Provide(service.NewDownloaderService),
		fx.Provide(service.NewIndexerService),
		fx.Provide(service.NewMigratorService),
		fx.Provide(service.NewPassportRepository),
		fx.Provide(service.NewUnarchiverService),

		fx.Provide(func(
			buildIndexCommand *command.BuildIndexCommand,
			httpServeCommand *command.HTTPServeCommand,
			migrateCommand *command.MigrateCommand,
			unarchiveCommand *command.UnarchiveCommand,
			updateSourceCommand *command.UpdateSourceCommand,
		) []contract.Commander {
			return []contract.Commander{
				buildIndexCommand,
				httpServeCommand,
				migrateCommand,
				unarchiveCommand,
				updateSourceCommand,
			}
		}),

		fx.Invoke(func(application *console.Application) error {
			if err := application.RunContext(context.Background(), os.Args); err != nil {
				return fmt.Errorf("application run: %w", err)
			}

			return nil
		}),
	).Run()
}

func env() error {
	if err := godotenv.Load(".env"); err != nil {
		return fmt.Errorf("load .env: %w", err)
	}

	_ = godotenv.Overload(".env.local")

	sentry.Init(sentry.ClientOptions{
		Dsn: os.Getenv("SENTRY_DSN"),
	})

	if os.Getenv("APP_ENV") == "dev" {
		logrus.SetLevel(logrus.DebugLevel)
	}

	var (
		blueDirectory  = os.Getenv("BLUE_DIRECTORY")
		greenDirectory = os.Getenv("GREEN_DIRECTORY")
	)

	_ = os.MkdirAll(blueDirectory, 0o0755)
	_ = os.MkdirAll(greenDirectory, 0o0755)

	return nil
}
