package main

import (
	"context"
	"egrul/internal"
	"net/http"
	"os"

	"github.com/joho/godotenv"
	"github.com/sirupsen/logrus"
	"go.uber.org/fx"
)

func main() {
	ctx, cancel := context.WithCancelCause(context.Background())
	defer cancel(nil)

	if err := godotenv.Load(".env"); err != nil && !os.IsNotExist(err) {
		logrus.WithError(err).Fatal("failed to load .env")
	}
	if err := godotenv.Overload(".env.local"); err != nil && !os.IsNotExist(err) {
		logrus.WithError(err).Fatal("failed to load .env.local")
	}

	if level, err := logrus.ParseLevel(os.Getenv("LOG_LEVEL")); err != nil {
		logrus.WithError(err).Fatal("failed to parse log level")
	} else {
		logrus.SetLevel(level)
	}

	app := fx.New(
		fx.Provide(
			internal.NewClient,
			internal.NewSessionManager,
			internal.NewSessionWatcher,
			internal.NewHierarchyBuilder,
			internal.NewPDFParser,
			internal.NewHTTPController,
			internal.NewParseController,
		),

		fx.Invoke(func(
			httpController *internal.HTTPController,
			parseController *internal.ParseController,
			sessionWatcher *internal.SessionWatcher,
		) error {
			go func() {
				http.Handle("/", httpController)
				http.Handle("/parse", parseController)
				logrus.WithContext(ctx).WithField("addr", os.Getenv("LISTEN_ADDR")).Info("listening on")
				if err := http.ListenAndServe(os.Getenv("LISTEN_ADDR"), nil); err != nil {
					cancel(err)
				}
			}()

			go func() {
				if err := sessionWatcher.Watch(ctx); err != nil {
					cancel(err)
				}
			}()

			<-ctx.Done()
			return ctx.Err()
		}),
	)

	app.Run()
}
