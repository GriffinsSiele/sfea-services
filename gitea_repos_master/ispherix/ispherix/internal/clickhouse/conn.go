package clickhouse

import (
	"fmt"
	"os"
	"strings"

	"github.com/ClickHouse/clickhouse-go/v2"
	"github.com/ClickHouse/clickhouse-go/v2/lib/driver"
)

type Conn struct {
	driver.Conn
}

func NewConn() (*Conn, error) {
	conn, err := clickhouse.Open(&clickhouse.Options{
		Addr: strings.Split(os.Getenv("CLICKHOUSE_ADDR"), ","),
	})

	if err != nil {
		return nil, fmt.Errorf("failed to connect to clickhouse: %w", err)
	}

	return &Conn{conn}, nil
}
