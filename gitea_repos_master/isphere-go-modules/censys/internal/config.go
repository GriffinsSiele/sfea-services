package internal

import (
	"os"

	"github.com/gin-gonic/gin"

	"github.com/getsentry/sentry-go"
	"github.com/joho/godotenv"
	"github.com/sirupsen/logrus"
)

type Config struct {
	Addr     string
	Host     string
	Username string
	Password string
	Debug    bool
}

func NewConfig() (*Config, error) {
	if err := godotenv.Load(".env"); err != nil {
		return nil, ErrEnvFile(".env")
	}

	_ = godotenv.Overload(".env.local")

	sentry.Init(sentry.ClientOptions{
		Dsn: os.Getenv("SENTRY_DSN"),
	})

	t := &Config{
		Addr:     os.Getenv("ADDR"),
		Host:     os.Getenv("CENSYS_HOST"),
		Username: os.Getenv("CENSYS_HTTP_BASIC_USER"),
		Password: os.Getenv("CENSYS_HTTP_BASIC_PASSWORD"),
		Debug:    os.Getenv("APP_DEBUG") == "true",
	}

	if t.Debug {
		gin.SetMode(gin.DebugMode)
		logrus.SetLevel(logrus.DebugLevel)
	} else {
		gin.SetMode(gin.ReleaseMode)
	}

	return t, nil
}
