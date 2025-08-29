package main

import (
	"fmt"
	"os"

	"github.com/gin-gonic/gin"
	_ "github.com/motemen/go-loghttp/global"
	"go.uber.org/fx"

	"git.i-sphere.ru/isphere-go-modules/callback/internal"
	"git.i-sphere.ru/isphere-go-modules/callback/internal/config"
	"git.i-sphere.ru/isphere-go-modules/callback/internal/connection"
	"git.i-sphere.ru/isphere-go-modules/callback/internal/contract"
	"git.i-sphere.ru/isphere-go-modules/callback/internal/controller"
	"git.i-sphere.ru/isphere-go-modules/callback/internal/net"
)

func main() {
	fx.New(
		fx.Provide(config.NewConfig),
		fx.Provide(connection.NewAMQP),
		fx.Provide(controller.NewDefaultController),
		fx.Provide(internal.NewRouter),
		fx.Provide(net.NewBalancer),

		fx.Provide(func(defaultController *controller.DefaultController) []contract.Controller {
			return []contract.Controller{
				defaultController,
			}
		}),

		fx.Invoke(func(engine *gin.Engine) error {
			if err := engine.Run(os.Getenv("ADDR")); err != nil {
				return fmt.Errorf("failed to listen http server: %w", err)
			}

			return nil
		}),
	).Run()
}
