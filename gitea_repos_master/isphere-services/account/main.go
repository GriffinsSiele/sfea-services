package main

import (
	"fmt"
	"os"

	"github.com/joho/godotenv"
	"github.com/sirupsen/logrus"
)

func main() {
	if err := load(); err != nil {
		logrus.Fatalf("failed to load: %v", err)
	}

	container, err := NewContainer()

	if err != nil {
		logrus.Fatalf("failed to create container: %v", err)

		return
	}

	if err := container.Invoke(func(app *App) error {
		if err := app.Run(); err != nil {
			return fmt.Errorf("failed to run application: %w", err)
		}

		return nil
	}); err != nil {
		logrus.Fatalf("failed to invoke app: %v", err)
	}
}

func load() error {
	if err := loadEnv(); err != nil {
		return fmt.Errorf("failed to load environment variables: %w", err)
	}

	if os.Getenv("APP_ENV") == "dev" {
		logrus.SetLevel(logrus.DebugLevel)
	}

	return nil
}

func loadEnv() error {
	if err := loadEnvFiles(); err != nil {
		return fmt.Errorf("failed to load env files: %w", err)
	}

	return nil
}

func loadEnvFiles() error {
	if err := godotenv.Load("./.env"); err != nil {
		return fmt.Errorf("failed to load .env: %w", err)
	}

	if _, err := os.Stat("./.env.local"); err != nil {
		return nil
	}

	if err := godotenv.Overload("./.env.local"); err != nil {
		return fmt.Errorf("failed to load .env.local: %w", err)
	}

	return nil
}
