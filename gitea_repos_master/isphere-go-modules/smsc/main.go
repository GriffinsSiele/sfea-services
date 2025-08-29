package main

import (
	"context"
	"os"

	"git.i-sphere.ru/isphere-go-modules/ripe/internal/command"
	"git.i-sphere.ru/isphere-go-modules/ripe/internal/handler"
	"github.com/getsentry/sentry-go"
	"github.com/gin-gonic/gin"
	"github.com/joho/godotenv"
	"github.com/meysamhadeli/problem-details"
	"github.com/sirupsen/logrus"
)

func main() {
	_ = godotenv.Load(".env")
	_ = godotenv.Overload(".env.local")

	sentry.Init(sentry.ClientOptions{
		Dsn: os.Getenv("SENTRY_DSN"),
	})

	var liveness bool

	for _, arg := range os.Args {
		if arg == "--liveness" {
			liveness = true
		}
	}

	if !liveness {
		e := gin.Default()
		e.Use(problemsMiddleware)
		e.POST("/api/v1/smsc", handler.Start)
		e.POST("/callback/smsc", handler.Callback)

		if err := e.Run(os.Getenv("ADDR")); err != nil {
			logrus.WithError(err).Fatalf("failed to listen server: %v", err)
		}
	} else {
		if err := command.Status(context.Background()); err != nil {
			logrus.WithError(err).Fatalf("failed to liveness probe: %v", err)
		}
	}
}

func problemsMiddleware(c *gin.Context) {
	c.Next()

	for _, err := range c.Errors {
		if _, err := problem.ResolveProblemDetails(c.Writer, c.Request, err); err != nil {
			logrus.WithError(err).Error("failed to resolve problem")
		}
	}
}
