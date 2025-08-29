package main

import (
	"fmt"
	"log/slog"
	"os"

	"github.com/joho/godotenv"
	"github.com/urfave/cli/v2"
	"go.uber.org/fx"
	appcli "i-sphere.ru/kube-utils/internal/cli"
	"i-sphere.ru/kube-utils/internal/command"
	"i-sphere.ru/kube-utils/internal/contract"
	"i-sphere.ru/kube-utils/internal/k8s"
)

func main() {
	if err := initEnv(); err != nil {
		slog.With("error", err).Error("failed to load env")
	}

	fx.New(fx.Module(
		"kube-utils",
		fx.Provide(
			appcli.NewApp,
			command.NewEnvironments,
			command.NewSecrets,
			k8s.NewClientset,
			k8s.NewConfig,
			k8s.NewDynamicClient,
		),
		fx.Provide(
			func(environmentsCmd *command.Environments, secretsCmd *command.Secrets) []contract.Commander {
				return []contract.Commander{
					environmentsCmd,
					secretsCmd,
				}
			},
		),
		fx.Invoke(func(app *cli.App) error {
			if err := app.Run(os.Args[:]); err != nil {
				slog.With("error", err).Error("failed to run app")
			}
			return nil
		}),
	)).Run()
}

func initEnv() error {
	if err := godotenv.Load(".env"); err != nil && !os.IsNotExist(err) {
		return fmt.Errorf("failed to load .env file: %w", err)
	}
	if err := godotenv.Overload(".env.local"); err != nil && !os.IsNotExist(err) {
		return fmt.Errorf("failed to load .env.local file: %w", err)
	}
	return nil
}
