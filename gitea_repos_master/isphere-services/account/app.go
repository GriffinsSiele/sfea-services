package main

import (
	"fmt"
	"os"

	"github.com/urfave/cli/v2"
)

type App struct {
	kernel *Kernel
}

func NewApp(kernel *Kernel) *App {
	return &App{
		kernel: kernel,
	}
}

func (t *App) Boot() error {
	if err := t.kernel.Build(); err != nil {
		return fmt.Errorf("failed to build container: %w", err)
	}

	return nil
}

func (t *App) Run() error {
	if err := t.Boot(); err != nil {
		return fmt.Errorf("failed to boot app: %w", err)
	}

	if err := t.kernel.Invoke(func(app *cli.App) error {
		if err := app.Run(os.Args); err != nil {
			return fmt.Errorf("failed to run cli app: %w", err)
		}

		return nil
	}); err != nil {
		return fmt.Errorf("failed to invoke cli app: %w", err)
	}

	return nil
}
