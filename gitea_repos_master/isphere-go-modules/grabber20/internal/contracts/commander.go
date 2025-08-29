package contracts

import "github.com/urfave/cli/v2"

const commanderGroupTag string = `group:"commander"`

type Commander interface {
	Describe() (*cli.Command, error)
	Action(*cli.Context) error
}

func AsCommander(t any) any {
	return AsOne[Commander](t, commanderGroupTag)
}

func WithCommanders() any {
	return WithMany[Commander](commanderGroupTag)
}
