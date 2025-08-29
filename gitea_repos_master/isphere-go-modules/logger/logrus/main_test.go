package logrus_test

import (
	"errors"
	"testing"

	ispherelogrus "git.i-sphere.ru/isphere-go-modules/logger/logrus"
	"github.com/sirupsen/logrus"
)

func init() {
	logrus.SetLevel(logrus.TraceLevel)
	logrus.SetFormatter(&ispherelogrus.ISphereFormatter{})
}

func TestTrace(t *testing.T) {
	logrus.Trace("test trace message")
	logrus.WithField("some", "value").Trace("test trace message with fields")
}

func TestDebug(t *testing.T) {
	logrus.Debug("test debug message")
	logrus.WithField("some", "value").Debug("test debug message with fields")
}

func TestInfo(t *testing.T) {
	logrus.Info("test info message")
	logrus.WithField("some", "value").Info("test info message with fields")
}

func TestWarn(t *testing.T) {
	logrus.Info("test warn message")
	logrus.WithField("some", "value").Warn("test warn message with fields")
	logrus.WithError(errors.New("test error")).Warn("test warn message with error")
	logrus.WithError(errors.New("test error")).WithField("some", "value").Warn("test warn message with fields and error")
}

func TestError(t *testing.T) {
	logrus.Error("test error message")
	logrus.WithField("some", "value").Error("test error message with fields")
	logrus.WithError(errors.New("test error")).Error("test error message with error")
	logrus.WithError(errors.New("test error")).WithField("some", "value").Error("test error message with fields and error")
}

func TestFatal(t *testing.T) {
	log := logrus.New()
	log.ExitFunc = func(i int) {}
	log.SetFormatter(&ispherelogrus.ISphereFormatter{})
	log.Fatal("test fatal message")
	log.WithField("some", "value").Fatal("test fatal message with fields")
	log.WithError(errors.New("test error")).Fatal("test fatal message with error")
	log.WithError(errors.New("test error")).WithField("some", "value").Fatal("test fatal message with fields and error")
}
