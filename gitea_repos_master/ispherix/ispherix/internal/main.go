package internal

import (
	"fmt"
	"log/slog"
	"os"

	"github.com/joho/godotenv"
)

func init() {
	if err := initEnv(); err != nil {
		slog.Error("failed to init env", "error", err)
		os.Exit(1)
	}

	if err := initLogger(); err != nil {
		slog.Error("failed to init logger", "error", err)
		os.Exit(1)
	}
}

func initEnv() error {
	if err := godotenv.Load(".env"); err != nil && !os.IsNotExist(err) {
		return fmt.Errorf("failed to load .env file: %w", err)
	}

	if err := godotenv.Overload(".env.local"); err != nil && !os.IsNotExist(err) {
		return fmt.Errorf("failed to load .env.local file: %w", err)
	}

	return nil
}

func initLogger() error {
	var level slog.Level

	switch os.Getenv("LOG_LEVEL") {
	case "debug":
		level = slog.LevelDebug
	case "info":
		level = slog.LevelInfo
	case "warn":
		level = slog.LevelWarn
	case "error":
		level = slog.LevelError
	default:
		return fmt.Errorf("invalid log level: %s", os.Getenv("LOG_LEVEL"))
	}

	slog.SetDefault(
		slog.New(
			slog.NewJSONHandler(os.Stdout, &slog.HandlerOptions{Level: level}),
		),
	)

	return nil
}
