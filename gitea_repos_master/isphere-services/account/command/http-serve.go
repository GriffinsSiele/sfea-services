package command

import (
	"fmt"
	"net"

	"github.com/gin-gonic/gin"
	"github.com/urfave/cli/v2"
)

const DefaultHTTPPort = 3000

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
		Category: "http",
		Name:     "http:serve",
		Action:   t.Execute,
		Flags: cli.FlagsByName{
			&cli.IntFlag{
				Name:    "port",
				Aliases: []string{"p"},
				Value:   DefaultHTTPPort,
			},
		},
	}
}

func (t *HTTPServe) Execute(ctx *cli.Context) error {
	addr := net.JoinHostPort("", ctx.String("port"))
	if err := t.router.Run(addr); err != nil {
		return fmt.Errorf("failed to run http server: %w", err)
	}

	return nil
}
