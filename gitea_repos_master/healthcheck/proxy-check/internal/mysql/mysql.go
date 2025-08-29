package mysql

import (
	"context"
	"database/sql"
	"fmt"

	_ "github.com/go-sql-driver/mysql"
	"go.i-sphere.ru/proxphere/internal/healthcheck"
)

type MySQL struct {
	dsn string
}

func NewMySQL(dsn string) *MySQL {
	return &MySQL{
		dsn: dsn,
	}
}

func (m *MySQL) GetProxies(ctx context.Context) ([]*healthcheck.Proxy, error) {
	db, err := sql.Open("mysql", m.dsn)
	if err != nil {
		return nil, fmt.Errorf("failed to open db: %w", err)
	}
	//goland:noinspection GoUnhandledErrorResult
	defer db.Close()

	// language=mysql
	query := `--
select id, server, port, login, password
from proxy
where proxygroup = 5
  and enabled = 1
`

	rows, err := db.QueryContext(ctx, query)
	if err != nil {
		return nil, fmt.Errorf("failed to execute query: %w", err)
	}

	var proxies []*healthcheck.Proxy

	for rows.Next() {
		proxy := &healthcheck.Proxy{}
		if err = rows.Scan(&proxy.ID, &proxy.Host, &proxy.Port, &proxy.Username, &proxy.Password); err != nil {
			return nil, fmt.Errorf("failed to scan row: %w", err)
		}

		proxies = append(proxies, proxy)
	}

	return proxies, nil
}
