package main

import (
	"context"
	"math/rand"
	"net/http"
	"os"
	"time"

	"github.com/getsentry/sentry-go"
	"github.com/joho/godotenv"
	"github.com/sirupsen/logrus"
	"i-sphere.ru/yoomoney/internal"
	"i-sphere.ru/yoomoney/pkg"
)

func init() {
	rand.Seed(time.Now().UnixNano())
}

func main() {
	if err := godotenv.Load(".env"); err != nil {
		logrus.WithError(err).Fatal("failed to load .env file")
	}
	if err := godotenv.Overload(".env.local"); err != nil && !os.IsNotExist(err) {
		logrus.WithError(err).Fatal("failed to load .env.local file")
	}

	if err := sentry.Init(sentry.ClientOptions{Dsn: os.Getenv("SENTRY_DSN")}); err != nil {
		logrus.WithError(err).Fatal("failed to init sentry")
	}

	ctx, cancel := context.WithCancelCause(context.Background())
	defer cancel(nil)

	client, err := internal.NewClient()
	if err != nil {
		logrus.WithError(err).Fatal("failed to create client")
	}

	sessionManager := pkg.NewSessionManager(client)
	sessionWatcher, err := pkg.NewSessionWatcher(sessionManager)
	if err != nil {
		logrus.WithContext(ctx).WithError(err).Fatal("failed to create session watcher")
	}
	httpController := pkg.NewHTTPController(client, sessionWatcher)

	go func() {
		http.Handle("/", httpController)
		logrus.WithContext(ctx).WithField("addr", os.Getenv("LISTEN_ADDR")).Info("listening on")
		if err := http.ListenAndServe(os.Getenv("LISTEN_ADDR"), nil); err != nil {
			cancel(err)
		}
	}()

	go func() {
		if err = sessionWatcher.Watch(ctx); err != nil {
			cancel(err)
		}
	}()

	<-ctx.Done()
	if err := ctx.Err(); err != nil {
		logrus.WithContext(ctx).WithError(err).Error("context error")
	}
}
