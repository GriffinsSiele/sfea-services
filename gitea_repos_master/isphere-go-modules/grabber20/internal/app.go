package internal

import (
	"fmt"

	"git.i-sphere.ru/grabber/internal/contracts"
	"github.com/urfave/cli/v2"
	"go.uber.org/fx"
)

func NewApp(commanders []contracts.Commander, shutdowner fx.Shutdowner) (*cli.App, error) {
	commands, err := newAppCommandsWithCommanders(commanders)
	if err != nil {
		return nil, fmt.Errorf("failed to create commands: %w", err)
	}

	return &cli.App{
		Name:     "grabber20",
		Commands: commands,
		After:    func(*cli.Context) error { return shutdowner.Shutdown() },
	}, nil
}

func newAppCommandsWithCommanders(commanders []contracts.Commander) (cli.Commands, error) {
	commands := make(cli.Commands, len(commanders))

	for i, commander := range commanders {
		command, err := commander.Describe()
		if err != nil {
			return nil, fmt.Errorf("failed to describe command: %w", err)
		}

		command.Action = commander.Action
		commands[i] = command
	}

	return commands, nil
}
