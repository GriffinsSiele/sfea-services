package command

import (
	"crypto/md5"
	"encoding/hex"
	"fmt"
	"sync"
	"sync/atomic"
	"time"

	"git.i-sphere.ru/isphere-go-modules/grabber/internal/fns"
	"git.i-sphere.ru/isphere-go-modules/grabber/internal/fns/model"
	"git.i-sphere.ru/isphere-go-modules/grabber/internal/util"
	"github.com/urfave/cli/v2"
)

type Debtam struct{}

func NewDebtam() *Debtam {
	return &Debtam{}
}

func (t *Debtam) Describe() *cli.Command {
	return &cli.Command{
		Category: "fns",
		Name:     "fns:debtam",
		Action:   t.Execute,
		Usage:    "Сведения о суммах недоимки и задолженности по пеням и штрафам",
		Flags: cli.FlagsByName{
			&cli.StringFlag{Name: "database", Required: true, EnvVars: []string{"ISPHERE_GRABBER_DATABASE"}},
			&cli.StringFlag{Name: "schema", Value: "fns"},
			&cli.StringFlag{Name: "table", Value: "debtam"},
			&cli.StringFlag{Name: "table-tax", Value: "debtam_taxes"},
			&cli.StringFlag{Name: "table-taxes", Value: "taxes"},
			&cli.BoolFlag{Name: "no-cache", Value: false},
			&cli.StringFlag{Name: "url", Value: fns.DebtamURL},
		},
	}
}

func (t *Debtam) Execute(c *cli.Context) error {
	var (
		ctx            = c.Context
		database       = c.String("database")
		schema         = c.String("schema")
		primaryTable   = c.String("table")
		secondaryTable = c.String("table-tax")
		taxesTable     = c.String("table-taxes")
		noCache        = c.Bool("no-cache")
		url            = c.String("url")
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

	rows, err := conn.Query(ctx, fmt.Sprintf("select id, code, title from %s.%s", schema, taxesTable))
	if err != nil {
		return fmt.Errorf("failed to query taxes")
	}

	defer rows.Close()

	var (
		taxes    sync.Map
		taxesSeq atomic.Uint32
		l        sync.Mutex
	)

	for rows.Next() {
		var (
			id    uint32
			code  string
			title string
		)

		if err := rows.Scan(&id, &code, &title); err != nil {
			return fmt.Errorf("failed to scan taxes: %w", err)
		}

		taxes.Store(title, NewTax(id, code, title, true))

		l.Lock()

		taxesSeqID := taxesSeq.Load()
		if taxesSeqID < id {
			taxesSeq.Store(id)
		}

		l.Unlock()
	}

	var taxSeq atomic.Uint32

	if err := fns.Invoke(ctx, database, url, noCache,
		func(obj *model.File[model.DebtamDocument], flush fns.FlushFunc) error {
			var (
				rows1 [][]any
				rows2 [][]any
			)

			for _, document := range obj.Documents {
				rows1 = append(rows1, []any{
					document.ID,
					document.Taxpayer.INN,
					document.GenerationDate.Format(time.DateOnly),
					document.StateDate.Format(time.DateOnly),
				})

				for _, tax := range document.Taxes {
					id := taxSeq.Add(1)

					if _, ok := taxes.Load(tax.Title); !ok {
						taxID := taxSeq.Add(1)

						taxes.Store(tax.Title, NewTax(taxID, "", tax.Title, false))
					}

					taxO, _ := taxes.Load(tax.Title)

					rows2 = append(rows2, []any{
						id,
						document.ID,
						taxO.(*Tax).ID,
						tax.Arrears,
						tax.Penalties,
						tax.Fines,
						tax.Sum,
					})
				}
			}

			if err := flush(rows1, []string{"id", "inn", "generation_date", "state_date"}, schema, primaryTable); err != nil {
				return fmt.Errorf("failed to flush primary data: %w", err)
			}

			if err := flush(rows2, []string{"id", "debtam_id", "tax_id", "arrears", "penalties", "fines", "sum"}, schema, secondaryTable); err != nil {
				return fmt.Errorf("failed to flush secondary data: %w", err)
			}

			return nil
		},
		func(flush fns.FlushFunc) error {
			var rows [][]any

			taxes.Range(func(key, value any) bool {
				if value.(*Tax).Exists {
					return true
				}

				rows = append(rows, []any{
					value.(*Tax).ID,
					value.(*Tax).Code,
					value.(*Tax).Title,
				})

				return true
			})

			if len(rows) > 0 {
				if err := flush(rows, []string{"id", "code", "title"}, schema, taxesTable); err != nil {
					return fmt.Errorf("failed to flush taxes data: %w", err)
				}
			}

			return nil
		},
		schema, primaryTable, secondaryTable); err != nil {
		return fmt.Errorf("failed to invoke: %w", err)
	}

	return nil
}

type Tax struct {
	ID     uint32
	Code   string
	Title  string
	Exists bool
}

func NewTax(id uint32, code, title string, exists bool) *Tax {
	if code == "" {
		hash := md5.Sum([]byte(title))
		code = hex.EncodeToString(hash[:])
	}

	return &Tax{
		ID:     id,
		Code:   code,
		Title:  title,
		Exists: exists,
	}
}
