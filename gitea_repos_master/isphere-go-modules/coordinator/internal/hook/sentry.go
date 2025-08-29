package hook

import (
	"errors"
	"fmt"
	"os"
	"time"

	"github.com/getsentry/sentry-go"
	"github.com/sirupsen/logrus"
)

type SentryHook struct{}

func NewSentryHook(dsn string) *SentryHook {
	if err := sentry.Init(sentry.ClientOptions{
		Dsn: dsn,
	}); err != nil {
		logrus.WithError(err).Fatal("failed to initialize sentry clients")
	}

	return &SentryHook{}
}

func (t *SentryHook) Levels() []logrus.Level {
	return []logrus.Level{
		logrus.PanicLevel,
		logrus.FatalLevel,
		logrus.ErrorLevel,
	}
}

func (t *SentryHook) Fire(e *logrus.Entry) error {
	hub := sentry.NewHub(sentry.CurrentHub().Client(), sentry.NewScope())
	hub.ConfigureScope(func(scope *sentry.Scope) {
		data := map[string]any{}
		for k, v := range e.Data {
			data[k] = fmt.Sprintf("%+v", v)
		}

		scope.SetContext("data", data)
	})

	hub.CaptureException(errors.New(e.Message))

	if !hub.Flush(5 * time.Second) {
		_, _ = fmt.Fprintf(os.Stderr, "[Sentry] failed to capture sentry error\n")
	}

	return nil
}
