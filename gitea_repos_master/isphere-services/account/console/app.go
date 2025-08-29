package console

import (
	"git.i-sphere.ru/isphere-services/login/contract"
	"github.com/urfave/cli/v2"
)

func NewApp(definitions []contract.Commander) *cli.App {
	commands := make([]*cli.Command, 0, len(definitions))
	for _, definition := range definitions {
		commands = append(commands, definition.Describe())
	}

	return &cli.App{
		Name:     "iSphere Account",
		Version:  "1.0.0",
		Commands: commands,
	}
}
