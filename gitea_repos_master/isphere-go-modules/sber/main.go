package main

import (
	"fmt"
	"os"

	"github.com/getsentry/sentry-go"
	"github.com/gin-gonic/gin"
	"github.com/joho/godotenv"
	"github.com/sirupsen/logrus"
	"github.com/soulkoden/logrusotel"
	"golang.org/x/net/context"
	"i-sphere.ru/sber/internal"
)

func main() {
	if err := loadEnv(); err != nil {
		logrus.WithError(err).Fatal("failed to load env")
	}

	if err := sentry.Init(sentry.ClientOptions{Dsn: os.Getenv("SENTRY_DSN")}); err != nil {
		logrus.WithError(err).Fatal("failed to init sentry")
	}

	tp, err := logrusotel.NewTracerProvider(os.Getenv("JAEGER_ENDPOINT"), "sber", false)
	if err != nil {
		logrus.WithError(err).Fatal("failed to create tracer provider")
	}
	//goland:noinspection GoUnhandledErrorResult
	defer tp.Shutdown(context.Background())

	logrus.AddHook(logrusotel.NewJaegerHook())

	client, err := internal.NewClient()
	if err != nil {
		logrus.WithError(err).Fatal("failed to create client")
	}

	ja3Loader, err := internal.NewJa3Loader()
	if err != nil {
		logrus.WithError(err).Fatal("failed to create ja3 loader")
	}
	handler := internal.NewHandler(client, tp.Tracer("sber"), ja3Loader)

	engine := gin.Default()
	engine.POST("/", handler.ServeHTTP)

	logrus.WithField("addr", os.Getenv("LISTEN_ADDR")).Info("starting server")
	if err = engine.Run(os.Getenv("LISTEN_ADDR")); err != nil {
		logrus.WithError(err).Fatal("failed to start server")
	}
}

func loadEnv() error {
	err := godotenv.Load(".env")
	if err != nil {
		return fmt.Errorf("failed to load .env file: %w", err)
	}

	err = godotenv.Overload(".env.local")
	if err != nil && !os.IsNotExist(err) {
		return fmt.Errorf("failed to load .env.local file: %w", err)
	}

	return nil
}
