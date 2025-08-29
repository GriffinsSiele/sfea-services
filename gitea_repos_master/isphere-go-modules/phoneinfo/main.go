package main

import (
	"fmt"
	"log/slog"
	"os"

	"git.i-sphere.ru/isphere-go-modules/phone/pkg"
	"git.i-sphere.ru/isphere-go-modules/phone/pkg/phonenumbers"
	"git.i-sphere.ru/isphere-go-modules/phone/pkg/rossvyaz"
	"github.com/joho/godotenv"
	"github.com/valyala/fasthttp"
)

func main() {
	if err := loadEnv(); err != nil {
		slog.With("error", err).Error("failed to load env")
		os.Exit(1)
	}

	addr := os.Getenv("ADDR")
	handler := pkg.NewHandler(phonenumbers.NewPhonenumbers(rossvyaz.NewRossvyaz()))

	slog.With("addr", addr).Info("starting server")

	if err := fasthttp.ListenAndServe(addr, handler.HandleFastHTTP); err != nil {
		slog.With("error", err).Error("failed to serve")
		os.Exit(1)
	}
}

func loadEnv() error {
	if err := godotenv.Load(".env"); err != nil && !os.IsNotExist(err) {
		return fmt.Errorf("failed to load .env file: %w", err)
	}
	if err := godotenv.Overload(".env.local"); err != nil && !os.IsNotExist(err) {
		return fmt.Errorf("failed to load .env.local file: %w", err)
	}
	return nil
}
