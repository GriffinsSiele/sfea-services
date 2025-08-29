package command

import (
	"fmt"
	"sync/atomic"
	"time"

	"git.i-sphere.ru/isphere-go-modules/grabber/internal/fns"
	"git.i-sphere.ru/isphere-go-modules/grabber/internal/fns/model"
	"github.com/urfave/cli/v2"
)

type Revexp struct{}

func NewRevexp() *Revexp {
	return &Revexp{}
}

func (t *Revexp) Describe() *cli.Command {
	return &cli.Command{
		Category: "fns",
		Name:     "fns:revexp",
		Action:   t.Execute,
		Usage:    `Сведения о суммах доходов и расходов по данным бухгалтерской (финансовой) отчетности организации за год, предшествующий году размещения таких сведений на сайте ФНС России`,
		Flags: cli.FlagsByName{
			&cli.StringFlag{Name: "database", Required: true, EnvVars: []string{"ISPHERE_GRABBER_DATABASE"}},
			&cli.StringFlag{Name: "schema", Value: "fns"},
			&cli.StringFlag{Name: "table", Value: "revexp"},
			&cli.BoolFlag{Name: "no-cache", Value: false},
			&cli.StringFlag{Name: "url", Value: fns.RevexpURL},
		},
	}
}

func (t *Revexp) Execute(c *cli.Context) error {
	var (
		ctx      = c.Context
		database = c.String("database")
		schema   = c.String("schema")
		table    = c.String("table")
		noCache  = c.Bool("no-cache")
		url      = c.String("url")
	)

	var documentSeq atomic.Uint32

	if err := fns.Invoke(ctx, database, url, noCache,
		func(obj *model.Revexp, flush fns.FlushFunc) error {
			var rows [][]any

			for _, document := range obj.Documents {
				for _, underpayment := range document.Underpayments {
					documentID := documentSeq.Add(1)

					rows = append(rows, []any{
						documentID,
						document.Subject.INN,
						underpayment.Income,
						underpayment.Expense,
						document.UpdatedAt.Format(time.DateOnly),
					})
				}
			}

			if err := flush(rows, []string{"id", "inn", "income", "expense", "updated_at"}, schema, table); err != nil {
				return fmt.Errorf("failed to flush data: %w", err)
			}

			return nil
		},
		func(fns.FlushFunc) error { return nil },
		schema, table); err != nil {
		return fmt.Errorf("failed to invoke: %w", err)
	}

	return nil
}
