package contracts

import "github.com/urfave/cli/v2"

type Server interface {
	Start(*cli.Context) error
}

const ServerGroupTag string = `group:"servers"`
