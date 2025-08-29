package clients

import (
	"database/sql"
	"os"

	_ "github.com/ClickHouse/clickhouse-go"
	"github.com/charmbracelet/log"
)

type Clickhouse struct {
	*sql.DB
}

func NewClickhouse() (*Clickhouse, error) {
	logger := log.WithPrefix("clients.Clickhouse")

	conn, err := sql.Open("clickhouse", os.Getenv("CLICKHOUSE_DSN"))
	if err != nil {
		logger.With("error", err).Error("failed to open clickhouse connection")
		return nil, nil
	}

	if err = conn.Ping(); err != nil {
		logger.With("error", err).Error("failed to ping clickhouse connection")
		return nil, nil
	}

	return &Clickhouse{conn}, nil
}
