package contract

import "github.com/urfave/cli/v2"

type Commander interface {
	Command() *cli.Command
}
