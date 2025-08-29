package command

import (
	"fmt"
	"sync/atomic"

	"git.i-sphere.ru/isphere-go-modules/grabber/internal/fns"
	"git.i-sphere.ru/isphere-go-modules/grabber/internal/fns/model"
	"github.com/urfave/cli/v2"
)

type Disqualifiedpersons struct{}

func NewDisqualifiedpersons() *Disqualifiedpersons {
	return &Disqualifiedpersons{}
}

func (t *Disqualifiedpersons) Describe() *cli.Command {
	return &cli.Command{
		Category: "fns",
		Name:     "fns:disqualifiedpersons",
		Action:   t.Execute,
		Usage:    "Юридические лица, в состав исполнительных органов которых входят дисквалифицированные лица",
		Flags: cli.FlagsByName{
			&cli.StringFlag{Name: "database", Required: true, EnvVars: []string{"ISPHERE_GRABBER_DATABASE"}},
			&cli.StringFlag{Name: "schema", Value: "fns"},
			&cli.StringFlag{Name: "table", Value: "disqualifiedpersons"},
			&cli.BoolFlag{Name: "no-cache", Value: false},
			&cli.StringFlag{Name: "url", Value: fns.DisqualifiedpersonsURL},
		},
	}
}

func (t *Disqualifiedpersons) Execute(c *cli.Context) error {
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
		func(obj *model.Disqualifiedperson, flush fns.FlushFunc) error {
			documentID := documentSeq.Add(1)

			rows = append(rows, []any{
				documentID,
				obj.INN,
				obj.KPP,
				obj.OGRN,
				obj.Name,
				obj.Address,
			})

			return nil
		},
		func(flush fns.FlushFunc) error {
			if err := flush(rows, []string{"id", "inn", "kpp", "ogrn", "org_name", "address"}, schema, table); err != nil {
				return fmt.Errorf("failed to flush data: %w", err)
			}

			return nil
		},
		schema, table); err != nil {
		return fmt.Errorf("failed to invoke: %w", err)
	}

	return nil
}
