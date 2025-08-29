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

func (t *App) Run() error {
	if err := t.boot(); err != nil {
		return fmt.Errorf("boot: %w", err)
	}

	if err := t.kernel.container.wrappedContainer.Invoke(func(cliApp *cli.App) error {
		if err := cliApp.Run(os.Args); err != nil {
			return fmt.Errorf("run CLI app: %w", err)
		}
		
		return nil
	}); err != nil {
		return fmt.Errorf("invoke CLI app: %w", err)
	}

	return nil
}

func (t *App) boot() error {
	if err := t.kernel.Build(); err != nil {
		return fmt.Errorf("kernel build: %w", err)
	}

	return nil
}
