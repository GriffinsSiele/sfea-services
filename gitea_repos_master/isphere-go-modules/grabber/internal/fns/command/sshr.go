package command

import (
	"fmt"
	"sync/atomic"
	"time"

	"git.i-sphere.ru/isphere-go-modules/grabber/internal/fns"
	"git.i-sphere.ru/isphere-go-modules/grabber/internal/fns/model"
	"git.i-sphere.ru/isphere-go-modules/grabber/internal/util"
	"github.com/urfave/cli/v2"
)

type Sshr struct{}

func NewSshr() *Sshr {
	return &Sshr{}
}

func (t *Sshr) Describe() *cli.Command {
	return &cli.Command{
		Category: "fns",
		Name:     "fns:sshr",
		Action:   t.Execute,
		Usage:    "Сведения о специальных налоговых режимах, применяемых налогоплательщиками",
		Flags: cli.FlagsByName{
			&cli.StringFlag{Name: "database", Required: true, EnvVars: []string{"ISPHERE_GRABBER_DATABASE"}},
			&cli.StringFlag{Name: "schema", Value: "fns"},
			&cli.StringFlag{Name: "table", Value: "sshr"},
			&cli.BoolFlag{Name: "no-cache", Value: false},
			&cli.StringFlag{Name: "url", Value: fns.SshrURL},
		},
	}
}

func (t *Sshr) Execute(c *cli.Context) error {
	var (
		ctx      = c.Context
		database = c.String("database")
		schema   = c.String("schema")
		table    = c.String("table")
		noCache  = c.Bool("no-cache")
		url      = c.String("url")
	)

	pool, err := util.PgConnect(ctx, database)
	if err != nil {
		return fmt.Errorf("failed to pg connect: %w", err)
	}

	defer pool.Close()

	conn, err := pool.Acquire(ctx)
	if err != nil {
		return fmt.Errorf("failed to acquire pg: %w", err)
	}

	defer conn.Release()

	var idSeq atomic.Uint64

	if err := fns.Invoke(ctx, database, url, noCache,
		func(obj *model.File[model.SshrDocument], flush fns.FlushFunc) error {
			var rows [][]any

			for _, document := range obj.Documents {
				for _, headcount := range document.Headcounts {
					documentId := idSeq.Add(1)

					rows = append(rows, []any{
						documentId,
						document.Taxpayer.INN,
						headcount.Count,
						document.StateDate.Format(time.DateOnly),
					})
				}
			}

			if err := flush(rows, []string{"id", "inn", "count", "updated_at"}, schema, table); err != nil {
				return fmt.Errorf("failed to flush primary data: %w", err)
			}

			return nil
		},
		func(flush fns.FlushFunc) error { return nil },
		schema, table); err != nil {
		return fmt.Errorf("failed to invoke: %w", err)
	}

	return nil
}
