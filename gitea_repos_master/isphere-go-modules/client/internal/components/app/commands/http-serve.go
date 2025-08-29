package commands

import (
	"fmt"
	"net"

	"github.com/gin-gonic/gin"
	"github.com/urfave/cli/v2"
)

const (
	defaultHost string = ""
	defaultPort uint   = 8000
)

type HTTPServe struct {
	router *gin.Engine
}

func NewHTTPServe(router *gin.Engine) *HTTPServe {
	return &HTTPServe{
		router: router,
	}
}

func (t *HTTPServe) Describe() *cli.Command {
	return &cli.Command{
		Name: "http:serve",
		Flags: cli.FlagsByName{
			&cli.StringFlag{
				Name:  "host",
				Value: defaultHost,
			},
			&cli.UintFlag{
				Name:  "port",
				Value: defaultPort,
			},
		},
	}
}

func (t *HTTPServe) Execute(ctx *cli.Context) error {
	addr := net.JoinHostPort(
		ctx.String("host"),
		ctx.String("port"),
	)

	if err := t.router.Run(addr); err != nil {
		return fmt.Errorf("failed to run gin server: %w", err)
	}

	return nil
}
