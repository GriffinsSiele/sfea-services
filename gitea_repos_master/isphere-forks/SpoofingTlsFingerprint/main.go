package main

import (
	"Golang/controller"
	"Golang/event_listener"
	"Golang/utils"
	"github.com/sirupsen/logrus"
	"os"
)

func main() {
	port := "8000"
	if len(os.Args) > 1 {
		port = os.Args[1]
	}

	if err := os.Setenv("tls13", "1"); err != nil {
		logrus.WithError(err).Errorf("%+v", err)
		return
	}

	app := NewApp(
		[]controller.Controller{
			controller.NewCheckStatusController(),
			controller.NewHandleController(
				utils.NewCookieUtil(),
			),
		},
		event_listener.NewRequestLoggerEventListener(),
		port,
	)

	if err := app.Run(); err != nil {
		logrus.WithError(err).Errorf("%+v", err)
	}
}
