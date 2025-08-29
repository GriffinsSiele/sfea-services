package contracts

import (
	"github.com/urfave/cli/v2"
	"go.uber.org/fx"
)

const CommanderTag = `group:"commanders"`

type Commander interface {
	Describe() *cli.Command
	Execute(*cli.Context) error
}

func AsCommander(t any) any {
	return fx.Annotate(t, fx.As(new(Commander)), fx.ResultTags(CommanderTag))
}
