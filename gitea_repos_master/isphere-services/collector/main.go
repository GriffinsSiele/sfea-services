package main

import (
	"fmt"
	"os"

	"github.com/joho/godotenv"
	"github.com/sirupsen/logrus"
)

func main() {
	if err := loadEnv(); err != nil {
		logrus.Fatalf("load env: %v", err)
	}

	if os.Getenv("APP_ENV") == "dev" {
		logrus.SetLevel(logrus.DebugLevel)
	}

	var (
		container = NewContainer()
		kernel    = NewKernel(container)
		app       = NewApp(kernel)
	)

	if err := app.Run(); err != nil {
		logrus.Fatalf("app run: %v", err)
	}
}

func loadEnv() error {
	if err := godotenv.Load(".env"); err != nil {
		return fmt.Errorf("load .env: %w", err)
	}

	if _, err := os.Stat(".env.local"); err != nil {
		if os.IsNotExist(err) {
			return nil
		}

		return fmt.Errorf("stat .env.local: %w", err)
	}

	if err := godotenv.Overload(".env.local"); err != nil {
		return fmt.Errorf("load .env.local: %w", err)
	}

	return nil
}
