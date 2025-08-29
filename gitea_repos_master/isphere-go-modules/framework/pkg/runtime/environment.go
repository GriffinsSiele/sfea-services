package runtime

import (
	"context"
	"fmt"
	"net/url"
	"os"
	"regexp"
	"strconv"
	"time"

	"git.i-sphere.ru/isphere-go-modules/framework/pkg/model"
	"github.com/joho/godotenv"
	"github.com/sirupsen/logrus"
)

func Invoke(fn func(ctx context.Context) error) error {
	_ = godotenv.Load(".env")
	_ = godotenv.Overload(".env.local")

	env := Environment("APP_ENV")

	_ = godotenv.Load(fmt.Sprintf(".env.%s", env))
	_ = godotenv.Overload(fmt.Sprintf(".env.%s.local", env))

	if err := fn(context.Background()); err != nil {
		return fmt.Errorf("failed to perform the runtime closure: %w", err)
	}

	return nil
}

var matchBoolRe = regexp.MustCompile(`(?mi)\btrue|1|yes|y\b`)

func Bool(key string) bool {
	return matchBoolRe.MatchString(String(key))
}

func Duration(key string) time.Duration {
	return time.Duration(Int(key)) * time.Second
}

func Environment(key string) model.Environment {
	return model.Environment(String(key))
}

func String(key string) string {
	value, ok := os.LookupEnv(key)
	if !ok {
		logrus.WithField("key", key).Fatal("required environment variable is not available")
	}

	return value
}

func Int(key string) int {
	valueString := String(key)

	value, err := strconv.Atoi(valueString)
	if err != nil {
		logrus.WithError(err).WithField("key", key).WithField("value", valueString).Fatal("cannot cast environment variable value as integer")
	}

	return value
}

func URL(key string) *url.URL {
	value := String(key)

	v, err := url.Parse(String(key))
	if err != nil {
		logrus.WithField("key", key).WithField("value", value).Fatal("parse URL environment value")
	}

	v2 := *v // clone

	return &v2
}
