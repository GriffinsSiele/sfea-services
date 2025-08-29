package main

import (
	"context"

	main_service "go.i-sphere.ru/proxphere-simple/internal/main-service"
	"go.i-sphere.ru/proxphere-simple/internal/rules"
	"go.i-sphere.ru/proxphere-simple/internal/socks5"
	"go.uber.org/fx"
)

func main() {
	fx.New(
		fx.Module(
			"main-service",

			fx.Provide(
				main_service.NewRepository,
			),
		),

		fx.Module(
			"rules",

			fx.Provide(
				rules.NewRepository,
			),
		),

		fx.Module(
			"socks5",

			fx.Provide(
				socks5.NewHandler,
				socks5.NewServer,
			),
		),

		fx.Invoke(func(mainServiceRepository *main_service.Repository, socks5Server *socks5.Server, shutdowner fx.Shutdowner) error {
			cancelCtx, cancel := context.WithCancel(context.Background())
			defer cancel()

			//goland:noinspection GoUnhandledErrorResult
			defer shutdowner.Shutdown()

			errCh := make(chan error)
			defer close(errCh)

			go func() {
				errCh <- mainServiceRepository.Listen(cancelCtx)
			}()

			go func() {
				errCh <- socks5Server.Start(cancelCtx)
			}()

			return <-errCh
		}),
	).Run()
}
