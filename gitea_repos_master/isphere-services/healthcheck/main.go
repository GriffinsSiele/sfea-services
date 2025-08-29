package main

import (
	"context"
	"fmt"
	"os"

	"go.uber.org/fx"
	"i-sphere.ru/healthcheck/internal/cli"
	"i-sphere.ru/healthcheck/internal/client"
	"i-sphere.ru/healthcheck/internal/contract"
	"i-sphere.ru/healthcheck/internal/env"
	"i-sphere.ru/healthcheck/internal/healthcheck"
	"i-sphere.ru/healthcheck/internal/k8s"
	"i-sphere.ru/healthcheck/internal/server"
	"i-sphere.ru/healthcheck/internal/storage"
)

func main() {
	ctx := context.Background()

	fx.New(fx.Module("healthcheck",
		fx.Provide(
			cli.NewNodeWorker,
			cli.NewServer,
			client.NewDirect,
			env.NewClusterInfo,
			env.NewParams,
			healthcheck.NewCoordinatorThroughHTTP,
			healthcheck.NewCoordinatorThroughMainService,
			healthcheck.NewCoordinatorThroughRabbitMQAndKeyDB,
			healthcheck.NewKeyDB,
			healthcheck.NewMainService,
			healthcheck.NewMysqlCheckTypeStatuses,
			healthcheck.NewRabbitMQ,
			healthcheck.NewTestError,
			healthcheck.NewTestErrorWithSubject,
			k8s.NewClientset,
			k8s.NewConfig,
			server.NewHTTPServer,
			storage.NewMemory,

			func(
				checkTypeStatuses *healthcheck.MysqlCheckTypeStatuses,
				coordinatorThroughHTTP *healthcheck.CoordinatorThroughHTTP,
				coordinatorThroughMainService *healthcheck.CoordinatorThroughMainService,
				coordinatorThroughRabbitMQAndKeyDB *healthcheck.CoordinatorThroughRabbitMQAndKeyDB,
				keyDB *healthcheck.KeyDB,
				mainService *healthcheck.MainService,
				rabbitMQ *healthcheck.RabbitMQ,
				testError *healthcheck.TestError,
				testErrorWithSubject *healthcheck.TestErrorWithSubject,
			) []contract.Healthchecker {
				return []contract.Healthchecker{
					checkTypeStatuses,
					coordinatorThroughHTTP,
					coordinatorThroughMainService,
					coordinatorThroughRabbitMQAndKeyDB,
					keyDB,
					mainService,
					rabbitMQ,
					testError,
					testErrorWithSubject,
				}
			},
		),

		fx.Invoke(func(
			httpServer *server.HTTPServer,
			memory *storage.Memory,
			nodeWorker *cli.NodeWorker,
			server *cli.Server,
			shutdowner fx.Shutdowner,
		) error {
			//goland:noinspection GoUnhandledErrorResult
			defer shutdowner.Shutdown()

			switch os.Args[1] {
			case "node-worker":
				go memory.Listen(ctx)

				//goland:noinspection GoUnhandledErrorResult
				go httpServer.Run(ctx)

				if err := nodeWorker.Run(ctx); err != nil {
					return fmt.Errorf("failed to run node worker: %w", err)
				}

			case "server":
				fallthrough

			default:
				go memory.Listen(ctx)

				//goland:noinspection GoUnhandledErrorResult
				go httpServer.RunAsMaster(ctx)

				if err := server.Run(ctx); err != nil {
					return fmt.Errorf("failed to run server: %w", err)
				}
			}

			return nil
		}),
	)).Run()
}
