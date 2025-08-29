package command

import (
	"fmt"
	"sync/atomic"
	"time"

	"git.i-sphere.ru/isphere-go-modules/grabber/internal/fns"
	"git.i-sphere.ru/isphere-go-modules/grabber/internal/fns/model"
	"git.i-sphere.ru/isphere-go-modules/grabber/internal/util"
	"github.com/jackc/pgx/v5"
	"github.com/sirupsen/logrus"
	"github.com/urfave/cli/v2"
)

type Rsmp struct{}

func NewRsmp() *Rsmp {
	return &Rsmp{}
}

func (t *Rsmp) Describe() *cli.Command {
	return &cli.Command{
		Category: "fns",
		Name:     "fns:rsmp",
		Action:   t.Execute,
		Usage:    "Единый реестр субъектов малого и среднего предпринимательства",
		Hidden:   true,
		Flags: cli.FlagsByName{
			&cli.StringFlag{Name: "database", Required: true, EnvVars: []string{"ISPHERE_GRABBER_DATABASE"}},
			&cli.StringFlag{Name: "schema", Value: "fns"},
			&cli.StringFlag{Name: "table", Value: "rsmp"},
			&cli.BoolFlag{Name: "no-cache", Value: false},
			&cli.StringFlag{Name: "url", Value: fns.RsmpURL},
		},
	}
}

func (t *Rsmp) Execute(c *cli.Context) error {
	var (
		ctx      = c.Context
		database = c.String("database")
		schema   = c.String("schema")
		table    = c.String("table")
		noCache  = c.Bool("no-cache")
		url      = c.String("url")
	)

	var (
		documents = make(chan *model.RsmpDocument)
		done      = make(chan any)
	)

	pool, err := util.PgConnect(ctx, database)
	if err != nil {
		return fmt.Errorf("failed to pg connect: %w", err)
	}

	defer pool.Close()

	conn, err := pool.Acquire(ctx)
	if err != nil {
		return fmt.Errorf("failed to acquire pg conn: %w", err)
	}

	defer conn.Release()

	var documentSeq atomic.Uint32

	flush := func(rows *[][]any) error {
		if _, err := conn.CopyFrom(ctx, pgx.Identifier{schema, table}, []string{"id", "inn", "type", "category", "employees", "created_at", "updated_at"}, pgx.CopyFromRows(*rows)); err != nil {
			return fmt.Errorf("failed to copy rows: %w", err)
		}

		*rows = make([][]any, 0)

		return nil
	}

	go func() {
		var rows [][]any

		for document := range documents {
			id := documentSeq.Add(1)

			rows = append(rows, []any{
				id, // id
				util.PgNullable(document.Subject().GetINN()),
				util.PgNullable(document.GetType()),
				util.PgNullable(document.GetCategory()),
				document.Employees,
				document.IncludeDate.Format(time.DateOnly),
				document.StateDate.Format(time.DateOnly),
			})

			if len(rows) > 100_000 {
				if err := flush(&rows); err != nil {
					logrus.WithError(err).Fatal("failed to copy rows")
				}
			}
		}

		if len(rows) > 0 {
			if err := flush(&rows); err != nil {
				logrus.WithError(err).Fatal("failed to copy rows")
			}
		}

		close(done)
	}()

	if err := fns.Invoke(ctx, database, url, noCache,
		func(obj *model.File[model.RsmpDocument], flush fns.FlushFunc) error {
			for _, document := range obj.Documents {
				documents <- document
			}

			return nil
		},
		func(flush fns.FlushFunc) error {
			close(documents)

			<-done

			return nil
		},
		schema, table); err != nil {
		return fmt.Errorf("failed to invoke: %w", err)
	}

	return nil
}
