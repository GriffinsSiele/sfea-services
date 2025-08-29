package main

import (
	"context"
	"healthcheck/pkg"
	"healthcheck/pkg/contracts"
	"healthcheck/pkg/metrics"
	"healthcheck/pkg/utils"
	"net/http"
	"os"

	"github.com/joho/godotenv"
	"github.com/pkg/errors"
	"github.com/sirupsen/logrus"
	"go.uber.org/fx"
)

func main() {
	fx.New(fx.Module("healthcheck",
		fx.Provide(
			pkg.NewServer,

			fx.Annotate(
				metrics.NewResponsesCount,
				fx.As(new(contracts.Collector)),
				fx.ResultTags(contracts.CollectorGroupTag),
			),

			fx.Annotate(
				metrics.NewResponsesDuration,
				fx.As(new(contracts.Collector)),
				fx.ResultTags(contracts.CollectorGroupTag),
			),

			fx.Annotate(
				metrics.NewSessionsEnoughCount,
				fx.As(new(contracts.Collector)),
				fx.ResultTags(contracts.CollectorGroupTag),
			),

			fx.Annotate(
				utils.ReturnArg[[]contracts.Collector],
				fx.ParamTags(contracts.CollectorGroupTag),
			),
		),

		fx.Invoke(func() error {
			if err := godotenv.Load(".env"); err != nil && !os.IsNotExist(err) {
				return errors.Wrap(err, "failed to load .env file")
			}
			if err := godotenv.Overload(".env.local"); err != nil && !os.IsNotExist(err) {
				return errors.Wrap(err, "failed to load .env.local file")
			}
			return nil
		}),

		fx.Invoke(func(collectors []contracts.Collector, server *http.Server) error {
			cancelCtx, cancel := context.WithCancel(context.Background())
			defer cancel()

			for _, collector := range collectors {
				if err := collector.Register(cancelCtx); err != nil {
					return errors.Wrap(err, "failed to register collector")
				}
			}

			logrus.WithContext(cancelCtx).WithField("addr", os.Getenv("LISTEN_ADDR")).Info("starting collectors")
			if err := http.ListenAndServe(os.Getenv("LISTEN_ADDR"), nil); err != nil {
				return errors.Wrap(err, "failed to start server")
			}

			return nil
		}),
	)).Run()
}
