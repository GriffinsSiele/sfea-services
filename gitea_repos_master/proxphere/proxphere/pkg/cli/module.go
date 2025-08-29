package cli

import (
	"context"
	"os"

	"github.com/charmbracelet/log"
	"go.uber.org/fx"

	"go.i-sphere.ru/proxy/pkg/adapters"
	"go.i-sphere.ru/proxy/pkg/clients"
	"go.i-sphere.ru/proxy/pkg/commands"
	"go.i-sphere.ru/proxy/pkg/commands/servers"
	"go.i-sphere.ru/proxy/pkg/contracts"
	"go.i-sphere.ru/proxy/pkg/handlers"
	"go.i-sphere.ru/proxy/pkg/managers"
	"go.i-sphere.ru/proxy/pkg/repositories"
	"go.i-sphere.ru/proxy/pkg/solutions"
	"go.i-sphere.ru/proxy/pkg/solutions/strategies"
)

func NewModule() fx.Option {
	return fx.Module("proxphere", Provide(), Invoke())
}

func Invoke() fx.Option {
	return fx.Invoke(func(app *App, proxySpecRepo *repositories.ProxySpec) error {
		cancelCtx, cancel := context.WithCancelCause(context.Background())
		defer cancel(nil)

		go proxySpecRepo.Listen(cancelCtx)

		go func() {
			cancel(app.RunContext(cancelCtx, os.Args))
		}()

		<-cancelCtx.Done()
		if err := cancelCtx.Err(); err != nil {
			log.WithPrefix("main").With("error", err).Error("context error")
		}
		return nil
	})
}

func Provide() fx.Option {
	return fx.Provide(
		adapters.NewProxySpec,
		annotateAs[contracts.Commander](commands.NewGenerateCertificate, contracts.CommanderGroupTag),
		annotateAs[contracts.Commander](commands.NewProxyCheck, contracts.CommanderGroupTag),
		annotateAs[contracts.Commander](servers.NewAll, contracts.CommanderGroupTag),
		annotateAs[contracts.Commander](servers.NewSOCKS5, contracts.CommanderGroupTag),
		annotateAs[contracts.Commander](servers.NewTCP, contracts.CommanderGroupTag),
		annotateAs[contracts.Commander](servers.NewTLS, contracts.CommanderGroupTag),
		annotateAs[contracts.Server](servers.NewSOCKS5, contracts.ServerGroupTag),
		annotateAs[contracts.Server](servers.NewTCP, contracts.ServerGroupTag),
		annotateAs[contracts.Server](servers.NewTLS, contracts.ServerGroupTag),
		annotateAs[contracts.SolutionStrategy](strategies.NewFast, contracts.SolutionStrategyGroupTag),
		annotateAs[contracts.SolutionStrategy](strategies.NewLast, contracts.SolutionStrategyGroupTag),
		annotateAs[contracts.SolutionStrategy](strategies.NewNotFail, contracts.SolutionStrategyGroupTag),
		annotateAs[contracts.SolutionStrategy](strategies.NewPass, contracts.SolutionStrategyGroupTag),
		annotateAs[contracts.SolutionStrategy](strategies.NewRandom, contracts.SolutionStrategyGroupTag),
		annotateAs[contracts.SolutionStrategy](strategies.NewTop, contracts.SolutionStrategyGroupTag),
		annotateWith[contracts.Commander](contracts.CommanderGroupTag),
		annotateWith[contracts.Server](contracts.ServerGroupTag),
		annotateWith[contracts.SolutionStrategy](contracts.SolutionStrategyGroupTag),
		clients.NewClickhouse,
		clients.NewHasura,
		clients.NewKafka,
		clients.NewMainService,
		clients.NewRedis,
		commands.NewGenerateCertificate,
		commands.NewProxyCheck,
		fx.Annotate(strategies.NewRandom, fx.As(new(contracts.DefaultSolutionStrategy))),
		handlers.NewSOCKS5,
		handlers.NewTCP,
		managers.NewProxySpec,
		NewApp,
		repositories.NewProxySpec,
		servers.NewAll,
		servers.NewSOCKS5,
		servers.NewTCP,
		servers.NewTLS,
		solutions.NewProxySpec,
		strategies.NewFast,
		strategies.NewLast,
		strategies.NewNotFail,
		strategies.NewPass,
		strategies.NewRandom,
		strategies.NewTop,
	)
}

func annotateAs[T any](v any, tags ...string) any {
	return fx.Annotate(v, fx.As(new(T)), fx.ResultTags(tags...))
}

func annotateWith[T any](tags ...string) any {
	return fx.Annotate(func(elems []T) []T {
		return elems
	}, fx.ParamTags(tags...))
}
