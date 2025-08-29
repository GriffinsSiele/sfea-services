package main

import (
	"context"
	"database/sql"
	"fmt"
	"net/url"
	"os"
	"time"

	_ "github.com/ClickHouse/clickhouse-go"
	"github.com/prometheus/client_golang/prometheus"
)

func newClickhouseTotalBytesGauge() *prometheus.GaugeVec {
	return prometheus.NewGaugeVec(
		prometheus.GaugeOpts{
			Name: "clickhouse_total_bytes",
		},
		[]string{"database", "table"},
	)
}

func newClickhouseTotalRowsGauge() *prometheus.GaugeVec {
	return prometheus.NewGaugeVec(
		prometheus.GaugeOpts{
			Name: "clickhouse_total_rows",
		},
		[]string{"database", "table"},
	)
}

func handleClickhouse(ctx context.Context, clickhouseTotalRowsGauge, clickhouseTotalBytesGauge *prometheus.GaugeVec) error {
	dsn := buildClickhouseDSN()

	db, err := sql.Open("clickhouse", dsn)
	if err != nil {
		return fmt.Errorf("failed to open ClickHouse connection: %w", err)
	}
	defer db.Close()

	if err := db.PingContext(ctx); err != nil {
		return fmt.Errorf("failed to ping ClickHouse: %w", err)
	}

	query := buildClickhouseQuery()

	for {
		select {
		case <-ctx.Done():
			return nil

		default:
			rows, err := db.QueryContext(ctx, query)
			if err != nil {
				return fmt.Errorf("failed to execute ClickHouse query: %w", err)
			}

			for rows.Next() {
				var database string
				var table string
				var totalRows float64
				var totalBytes float64

				if err := rows.Scan(&database, &table, &totalRows, &totalBytes); err != nil {
					return fmt.Errorf("failed to scan ClickHouse query result: %w", err)
				}

				setMetric(clickhouseTotalRowsGauge, database, table, totalRows)
				setMetric(clickhouseTotalBytesGauge, database, table, totalBytes)
			}

			rows.Close()

			time.Sleep(5 * time.Second)
		}
	}
}

func buildClickhouseDSN() string {
	dsn := url.URL{
		Scheme: "tcp",
		Host:   os.Getenv("CLICKHOUSE_HOST"),
		RawQuery: url.Values{
			"username": []string{os.Getenv("CLICKHOUSE_USERNAME")},
			"password": []string{os.Getenv("CLICKHOUSE_PASSWORD")},
			"database": []string{os.Getenv("CLICKHOUSE_DATABASE")},
		}.Encode(),
	}
	return dsn.String()
}

func buildClickhouseQuery() string {
	// language=clickhouse
	return fmt.Sprintf(
		`select
    database,
    table,
    total_rows,
    total_bytes
from
    system.tables
where database = '%s'`, os.Getenv("CLICKHOUSE_DATABASE"))
}

func setMetric(metric *prometheus.GaugeVec, database, table string, value float64) {
	metric.With(prometheus.Labels{
		"database": database,
		"table":    table,
	}).Set(value)
}
