package config

import (
	"fmt"
	"os"

	"git.i-sphere.ru/isphere-go-modules/grabber/internal/contract"
	"github.com/joho/godotenv"
	"github.com/sirupsen/logrus"
)

type Config struct{}

func NewConfig() (*Config, error) {
	logrus.SetLevel(logrus.DebugLevel)

	if err := godotenv.Load(".env"); err != nil {
		return nil, fmt.Errorf("failed to load .env: %w", err)
	}

	_ = godotenv.Overload(".env.local")

	if err := os.MkdirAll(contract.CacheDir, 0o0755); err != nil {
		return nil, fmt.Errorf("failde to create cache dir: %w", err)
	}

	return &Config{}, nil
}
