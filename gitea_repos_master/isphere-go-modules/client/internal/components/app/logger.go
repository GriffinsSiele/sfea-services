package app

import (
	"github.com/sirupsen/logrus"
	"github.com/takt-corp/fx-logrus"
	"go.uber.org/fx/fxevent"
)

func NewLogger() fxevent.Logger {
	return &fxlogrus.LogrusLogger{
		Logger: logrus.StandardLogger(),
	}
}
