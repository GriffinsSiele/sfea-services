package main

import (
	"fmt"
	"os"
	"strconv"

	"github.com/joho/godotenv"
	"go.uber.org/fx"
	"go.uber.org/fx/fxevent"
	"go.uber.org/zap"
	"go.uber.org/zap/zapcore"

	"i-sphere.ru/isto/internal/contracts"
	"i-sphere.ru/isto/internal/controllers"
	"i-sphere.ru/isto/internal/databases"
	"i-sphere.ru/isto/internal/servers"
	"i-sphere.ru/isto/internal/storages"
)

func main() {
	mustLoadEnv()
	mustConfigureLogger()

	fx.New(
		newFxLogger(),
		fx.Provide(
			databases.NewMongo,
			servers.NewHTTP,
			servers.NewMux,
			storages.NewCollector,
		),
		fx.Provide(
			annotate[contracts.Controller](controllers.NewFile, "controllers"),
			annotate[contracts.Controller](controllers.NewHealth, "controllers"),
			annotate[contracts.Controller](controllers.NewStat, "controllers"),
			use[contracts.Controller]("controllers"),
		),
		fx.Invoke(func(httpServer *servers.HTTP, shutdowner fx.Shutdowner) error {
			//goland:noinspection GoUnhandledErrorResult
			defer shutdowner.Shutdown()

			zap.L().Info("HTTP server started", zap.String("addr", httpServer.Addr))
			if err := httpServer.ListenAndServe(); err != nil {
				return fmt.Errorf("failed to start HTTP server: %w", err)
			}

			return nil
		}),
	).Run()
}

func mustLoadEnv() {
	if err := godotenv.Load(".env"); err != nil && !os.IsNotExist(err) {
		zap.L().Fatal("failed to load .env", zap.Error(err))
		return
	}

	if err := godotenv.Overload(".env.local"); err != nil && !os.IsNotExist(err) {
		zap.L().Fatal("failed to load .env.local", zap.Error(err))
		return
	}
}

func mustConfigureLogger() {
	var logger *zap.Logger

	switch os.Getenv("APP_ENV") {
	case "prod":
		logger = zap.Must(zap.NewProduction())
	case "dev":
		logger = zap.Must(zap.NewDevelopment())
	default:
		zap.L().Fatal("unknown env", zap.String("app_env", os.Getenv("APP_ENV")))
		return
	}

	zap.ReplaceGlobals(logger)
}

func annotate[T any](v any, g string) any {
	return fx.Annotate(v, fx.As(new(T)), fx.ResultTags(groupToTag(g)))
}

func use[T any](g string) any {
	return fx.Annotate(func(elems []T) []T {
		return elems
	}, fx.ParamTags(groupToTag(g)))
}

func groupToTag(g string) string {
	return "group:" + strconv.Quote(g)
}

func newFxLogger() fx.Option {
	l := &fxevent.ZapLogger{
		Logger: zap.L(),
	}

	l.UseLogLevel(zapcore.DebugLevel)

	return fx.WithLogger(func() fxevent.Logger {
		return l
	})
}
