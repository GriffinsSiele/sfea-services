package cli

import (
	"fmt"
	"os"

	"github.com/charmbracelet/log"
	"github.com/joho/godotenv"
)

func MustLoadEnv() {
	if err := LoadEnv(); err != nil {
		log.WithPrefix("cli.MustLoadEnv()").With("error", err).Fatal("failed to load environment variables")
	}
}

func LoadEnv() error {
	if err := godotenv.Load(".env"); err != nil && !os.IsNotExist(err) {
		return fmt.Errorf("failed to load .env: %w", err)
	}
	if err := godotenv.Overload(".env.local"); err != nil && !os.IsNotExist(err) {
		return fmt.Errorf("failed to load .env.local: %w", err)
	}

	logLevelStr := os.Getenv("LOG_LEVEL")
	logLevel, err := log.ParseLevel(logLevelStr)
	if err != nil {
		return fmt.Errorf("failed to parse log level: %w", err)
	}
	log.SetLevel(logLevel)

	return nil
}
