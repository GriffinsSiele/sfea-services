package internal

import (
	"gitea-http.gitea.svc.cluster.local/isphere-go-modules/coordinator/internal/clients/clickhouse"
	"gitea-http.gitea.svc.cluster.local/isphere-go-modules/coordinator/internal/clients/graphql"
	"gitea-http.gitea.svc.cluster.local/isphere-go-modules/coordinator/internal/clients/keydb"
	"gitea-http.gitea.svc.cluster.local/isphere-go-modules/coordinator/internal/clients/shell"
	"gitea-http.gitea.svc.cluster.local/isphere-go-modules/coordinator/internal/clients/tcp"
	"gitea-http.gitea.svc.cluster.local/isphere-go-modules/coordinator/internal/command"
	"gitea-http.gitea.svc.cluster.local/isphere-go-modules/coordinator/internal/config"
	"gitea-http.gitea.svc.cluster.local/isphere-go-modules/coordinator/internal/console"
	"gitea-http.gitea.svc.cluster.local/isphere-go-modules/coordinator/internal/contract"
	"gitea-http.gitea.svc.cluster.local/isphere-go-modules/coordinator/internal/controller"
	"gitea-http.gitea.svc.cluster.local/isphere-go-modules/coordinator/internal/hydrator"
	"gitea-http.gitea.svc.cluster.local/isphere-go-modules/coordinator/internal/manager"
	"gitea-http.gitea.svc.cluster.local/isphere-go-modules/coordinator/internal/repository"
	"github.com/sirupsen/logrus"
	fxlogrus "github.com/takt-corp/fx-logrus"
	"go.uber.org/fx"
	"go.uber.org/fx/fxevent"
)

func Module() fx.Option {
	return fx.Options(
		fx.WithLogger(func() fxevent.Logger {
			return &fxlogrus.LogrusLogger{Logger: logrus.StandardLogger()}
		}),

		fx.Provide(clickhouse.NewClient),
		fx.Provide(clickhouse.NewPool),
		fx.Provide(shell.NewClient),
		fx.Provide(shell.NewPool),
		fx.Provide(graphql.NewClient),
		fx.Provide(graphql.NewPool),
		fx.Provide(keydb.NewClient),
		fx.Provide(keydb.NewPool),
		fx.Provide(tcp.NewClient),
		fx.Provide(tcp.NewPool),
		fx.Provide(command.NewCleanUpCommand),
		fx.Provide(command.NewClientCommand),
		fx.Provide(command.NewGenerateConfig),
		fx.Provide(command.NewHTTPServeCommand),
		fx.Provide(command.NewInvokeCommand),
		fx.Provide(command.NewLivenessCommand),
		fx.Provide(command.NewMessengerConsumeCommand),
		fx.Provide(command.NewShellCommand),
		fx.Provide(config.NewConfig),
		fx.Provide(console.NewApplication),
		fx.Provide(controller.NewCheckTypeController),
		fx.Provide(controller.NewCheckTypesController),
		fx.Provide(controller.NewSandboxController),
		fx.Provide(hydrator.NewCheckType),
		fx.Provide(manager.NewCheckTypeManager),
		fx.Provide(manager.NewProviderManager),
		fx.Provide(repository.NewCheckTypeRepository),
		fx.Provide(repository.NewProviderRepository),

		fx.Provide(func(
			checkTypeController *controller.CheckTypeController,
			checkTypesController *controller.CheckTypesController,
			sandboxController *controller.SandboxController,
		) []contract.Controller {
			return []contract.Controller{
				checkTypeController,
				checkTypesController,
				sandboxController,
			}
		}),

		fx.Provide(func(
			cleanUpCommand *command.CleanUpCommand,
			clientCommand *command.ClientCommand,
			generateConfigCommand *command.GenerateConfig,
			httpServeCommand *command.HTTPServeCommand,
			invokeCommand *command.InvokeCommand,
			livenessCommand *command.LivenessCommand,
			messengerConsumeCommand *command.MessengerConsumeCommand,
			shellCommand *command.ShellCommand,
		) []contract.Commander {
			return []contract.Commander{
				cleanUpCommand,
				clientCommand,
				generateConfigCommand,
				httpServeCommand,
				invokeCommand,
				livenessCommand,
				messengerConsumeCommand,
				shellCommand,
			}
		}),
	)
}
