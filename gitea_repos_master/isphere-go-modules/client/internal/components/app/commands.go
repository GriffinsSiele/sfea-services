package app

import (
	"github.com/urfave/cli/v2"

	"git.i-sphere.ru/client/internal/contracts"
)

func NewCommands(commanders []contracts.Commander) []*cli.Command {
	commands := make([]*cli.Command, len(commanders))

	for i, commander := range commanders {
		command := commander.Describe()
		command.Action = commander.Execute
		commands[i] = command
	}

	return commands
}
