package command

import (
	"fmt"
	"sync/atomic"

	"git.i-sphere.ru/isphere-go-modules/grabber/internal/fns"
	"git.i-sphere.ru/isphere-go-modules/grabber/internal/fns/model"
	"git.i-sphere.ru/isphere-go-modules/grabber/internal/util"
	"github.com/sirupsen/logrus"
	"github.com/urfave/cli/v2"
)

type Massfounders struct{}

func NewMassfounders() *Massfounders {
	return &Massfounders{}
}

func (t *Massfounders) Describe() *cli.Command {
	return &cli.Command{
		Category: "fns",
		Name:     "fns:massfounders",
		Action:   t.Execute,
		Usage:    "Сведения о физических лицах, являющихся учредителями (участниками) нескольких юридических лиц",
		Flags: cli.FlagsByName{
			&cli.StringFlag{Name: "database", Required: true, EnvVars: []string{"ISPHERE_GRABBER_DATABASE"}},
			&cli.StringFlag{Name: "schema", Value: "fns"},
			&cli.StringFlag{Name: "table", Value: "massfounders"},
			&cli.BoolFlag{Name: "no-cache", Value: false},
			&cli.StringFlag{Name: "url", Value: fns.MassfoundersURL},
		},
	}
}

func (t *Massfounders) Execute(c *cli.Context) error {
	var (
		ctx      = c.Context
		database = c.String("database")
		schema   = c.String("schema")
		table    = c.String("table")
		noCache  = c.Bool("no-cache")
		url      = c.String("url")
	)

	var (
		documentSeq atomic.Uint32
		rows        [][]any
	)

	if err := fns.Invoke(ctx, database, url, noCache,
		func(obj *model.Massfounder, flush fns.FlushFunc) error {
			documentID := documentSeq.Add(1)

			if obj.INN == "" {
				logrus.WithField("massfounder", obj).Warn("massfounder have no inn")

				return nil
			}

			rows = append(rows, []any{
				documentID,
				obj.INN,
				util.PgNullable(obj.Surname),
				util.PgNullable(obj.Name),
				util.PgNullable(obj.Patronymic),
				obj.Count,
			})

			return nil
		},
		func(flush fns.FlushFunc) error {
			if err := flush(rows, []string{"id", "inn", "surname", "name", "patronymic", "count"}, schema, table); err != nil {
				return fmt.Errorf("failed to flush data: %w", err)
			}

			return nil
		},
		schema, table); err != nil {
		return fmt.Errorf("failed to invoke: %w", err)
	}

	return nil
}
