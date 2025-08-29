package main

import (
	"context"
	"fmt"
	"log/slog"
	"os"
	"time"

	"github.com/joho/godotenv"
	"go.i-sphere.ru/proxphere/internal/clickhouse"
	"go.i-sphere.ru/proxphere/internal/healthcheck"
	"go.i-sphere.ru/proxphere/internal/mysql"
)

func main() {
	if err := godotenv.Load(".env"); err != nil {
		slog.Error("failed to load .env", "error", err)
		os.Exit(1)
	}

	if err := godotenv.Overload(".env.local"); err != nil && !os.IsNotExist(err) {
		slog.Error("failed to load .env.local", "error", err)
		os.Exit(1)
	}

	ctx := context.Background()

	if err := listen(ctx); err != nil {
		slog.ErrorContext(ctx, "failed to listen", "error", err)
		os.Exit(1)
	}
}

func listen(ctx context.Context) error {
	mysqlService := mysql.NewMySQL(os.Getenv("MYSQL_DSN"))
	healthcheckService := healthcheck.NewHealthcheck(os.Getenv("HEALTHCHECK_SERVICE_ENDPOINT"))
	clickhouseService := clickhouse.NewClickhouse(os.Getenv("CLICKHOUSE_ENDPOINT"))

	healthcheckInterval, err := time.ParseDuration(os.Getenv("HEALTHCHECK_INTERVAL"))
	if err != nil {
		return fmt.Errorf("failed to parse HEALTHCHECK_INTERVAL: %w", err)
	}

	if err = exec(ctx, mysqlService, healthcheckService, clickhouseService); err != nil {
		return fmt.Errorf("failed to startup: %w", err)
	}

	slog.InfoContext(ctx, "listening proxies")

	for {
		select {
		case <-ctx.Done():
			return ctx.Err()
		case <-time.After(healthcheckInterval):
		}

		if err = exec(ctx, mysqlService, healthcheckService, clickhouseService); err != nil {
			return fmt.Errorf("failed to iterate: %w", err)
		}
	}
}

func exec(
	ctx context.Context,
	mysqlService *mysql.MySQL,
	healthcheckService *healthcheck.Healthcheck,
	clickhouseService *clickhouse.Clickhouse,
) error {
	proxies, err := mysqlService.GetProxies(ctx)
	if err != nil {
		return fmt.Errorf("failed to get proxies: %w", err)
	}

	checkCtx, cancel := context.WithTimeout(ctx, 30*time.Second)
	defer cancel()

	states, err := healthcheckService.CheckAll(checkCtx, proxies)
	if err != nil {
		return fmt.Errorf("healthcheck failed: %w", err)
	}

	for _, state := range states {
		logState(ctx, state)
	}

	if err = clickhouseService.Save(ctx, states); err != nil {
		return fmt.Errorf("failed to save to clickhouse: %w", err)
	}

	return nil
}

func logState(ctx context.Context, state *healthcheck.State) {
	log := slog.Default()

	if p := state.Proxy; p != nil {
		log = log.With("proxy_id", p.ID)
	}

	log = slog.With(
		"start_time", state.StartTime,
		"dial_duration", state.DialDuration,
		"connect_duration", state.ConnectDuration,
		"response_duration", state.ResponseDuration,
		"ip", state.IP,
	)

	if err := state.Error; err != nil {
		log.ErrorContext(ctx, "failed proxy check", "error", err)
	} else {
		log.InfoContext(ctx, "success proxy check")
	}
}
