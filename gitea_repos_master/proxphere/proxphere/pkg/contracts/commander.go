package contracts

import (
	"github.com/urfave/cli/v2"
)

type Commander interface {
	NewCommand() *cli.Command
}

const CommanderGroupTag string = `group:"commanders"`
