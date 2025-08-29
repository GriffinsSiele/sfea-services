package main

import (
	"context"
	"errors"
	"fmt"
	"log/slog"
	"os"
	"sink/pkg/clients"
	"sink/pkg/commands"

	_ "github.com/davecgh/go-spew/spew"
	"github.com/joho/godotenv"
	"github.com/urfave/cli/v2"
)

func main() {
	ctx := context.Background()
	if err := run(ctx); err != nil {
		slog.ErrorContext(ctx, "failed to run sink", "error", err)
		os.Exit(-1)
	}
}

func run(ctx context.Context) error {
	if err := godotenv.Load(".env"); err != nil && !errors.Is(err, os.ErrNotExist) {
		return fmt.Errorf("failed to load environment variables: %w", err)
	}
	if err := godotenv.Overload(".env.local"); err != nil && !errors.Is(err, os.ErrNotExist) {
		return fmt.Errorf("failed to load local environment variables: %w", err)
	}

	debeziumClient := clients.NewDebezium()
	sourcesConfigureCommand := commands.NewSourcesConfigure(debeziumClient)
	sourcesCleanCommand := commands.NewSourcesClean(debeziumClient)

	app := &cli.App{
		Name: "sink",
		Commands: cli.CommandsByName{
			&cli.Command{
				Category: "sources",
				Name:     "sources:configure",
				Action:   sourcesConfigureCommand.Invoke,
			},
			&cli.Command{
				Category: "sources",
				Name:     "sources:clean",
				Action:   sourcesCleanCommand.Invoke,
			},
		},
	}

	if err := app.RunContext(ctx, os.Args); err != nil {
		return fmt.Errorf("failed to run: %w", err)
	}

	return nil
}
