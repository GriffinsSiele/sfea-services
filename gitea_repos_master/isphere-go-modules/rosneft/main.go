package main

import (
	"log/slog"
	"os"

	"github.com/getsentry/sentry-go"
	"github.com/gin-gonic/gin"
	"github.com/joho/godotenv"
	"github.com/pkg/errors"
	"i-sphere.ru/rosneft/internal"
)

func main() {
	if err := run(); err != nil {
		slog.With("error", err).Error("failed to run")
		os.Exit(1)
	}
}

func run() error {
	if err := godotenv.Load(".env"); err != nil {
		return errors.Wrap(err, "failed to load .env")
	}
	if err := godotenv.Overload(".env.local"); err != nil && !os.IsNotExist(err) {
		return errors.Wrap(err, "failed to load .env.local")
	}

	if err := sentry.Init(sentry.ClientOptions{Dsn: os.Getenv("SENTRY_DSN")}); err != nil {
		return errors.Wrap(err, "failed to init sentry")
	}

	client, err := internal.NewClient()
	if err != nil {
		return errors.Wrap(err, "failed to create client")
	}

	handler := internal.NewHandler(client)

	engine := gin.Default()
	engine.POST("/", handler.ServeHTTP)

	slog.With("addr", os.Getenv("LISTEN_ADDR")).Info("starting server")
	if err = engine.Run(os.Getenv("LISTEN_ADDR")); err != nil {
		return errors.Wrap(err, "failed to run server")
	}

	return nil
}
