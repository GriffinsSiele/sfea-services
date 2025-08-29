package command

import (
	"fmt"
	"strings"

	"github.com/jackc/pgx/v5/pgxpool"
	"github.com/sirupsen/logrus"
	"github.com/urfave/cli/v2"
)

type FacebookIndex struct{}

func NewFacebookIndex() *FacebookIndex {
	return &FacebookIndex{}
}

func (t *FacebookIndex) Describe() *cli.Command {
	return &cli.Command{
		Category: "facebook",
		Name:     "facebook:index",
		Action:   t.Execute,
		Usage:    "Создание индекса в таблицах базы данных утечки Facebook",
		Flags: cli.FlagsByName{
			&cli.StringFlag{Name: "database", Required: true, EnvVars: []string{"ISPHERE_GRABBER_DATABASE"}},
			&cli.StringFlag{Name: "schema", Value: "facebook"},
		},
	}
}

func (t *FacebookIndex) Execute(c *cli.Context) error {
	var (
		ctx = c.Context

		databaseStr = c.String("database")
		schema      = c.String("schema")
	)

	pool, err := pgxpool.New(ctx, databaseStr)
	if err != nil {
		return fmt.Errorf("failed to create pgxpool: %w", err)
	}

	defer pool.Close()

	conn, err := pool.Acquire(ctx)
	if err != nil {
		return fmt.Errorf("failed to acquire db: %w", err)
	}

	defer conn.Release()

	tables := map[string]int{}
	rows, err := conn.Query(ctx, `select tablename
from pg_catalog.pg_tables
where schemaname = $1
`, schema)
	if err != nil {
		return fmt.Errorf("failed to get tables: %w", err)
	}

	for rows.Next() {
		var table string
		if err = rows.Scan(&table); err != nil {
			return fmt.Errorf("failed to scan tablename: %w", err)
		}

		tables[table] = 1
	}

	rows, err = conn.Query(ctx, `select tablename, indexname
from pg_indexes
where schemaname = $1
order by tablename, indexname;
`, schema)
	if err != nil {
		return fmt.Errorf("failed to get indexes: %w", err)
	}

	for rows.Next() {
		var table, index string
		if err = rows.Scan(&table, &index); err != nil {
			return fmt.Errorf("failed to scan tablename with indexname: %w", err)
		}

		suffix := strings.TrimPrefix(index, table)
		if suffix == "_id_idx" || suffix == "_phone_idx" {
			delete(tables, table)
		}
	}

	for table := range tables {
		for _, name := range []string{"id", "phone"} {
			sql := fmt.Sprintf("create index %s_%s_idx on facebook.%s using btree (%s)",
				table,
				name,
				table,
				name,
			)

			logrus.Info(sql)

			if _, err = conn.Exec(ctx, sql); err != nil {
				return fmt.Errorf("failed to create an index: %s: %s: %w", table, name, err)
			}
		}
	}

	return nil
}
