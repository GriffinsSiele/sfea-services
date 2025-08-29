package contracts

import "github.com/urfave/cli/v2"

type Invoker interface {
	Invoke(ctx *cli.Context) error
}
