package command

import (
	"fmt"
	"strings"
	"sync/atomic"

	"git.i-sphere.ru/isphere-go-modules/grabber/internal/fns"
	"git.i-sphere.ru/isphere-go-modules/grabber/internal/fns/model"
	"github.com/urfave/cli/v2"
)

type Masaddress struct{}

func NewMassAddress() *Masaddress {
	return &Masaddress{}
}

func (t *Masaddress) Describe() *cli.Command {
	return &cli.Command{
		Category: "fns",
		Name:     "fns:masaddress",
		Action:   t.Execute,
		Usage:    "Адреса, указанные при государственной регистрации в качестве места нахождения несколькими юридическими лицами",
		Flags: cli.FlagsByName{
			&cli.StringFlag{Name: "database", Required: true, EnvVars: []string{"ISPHERE_GRABBER_DATABASE"}},
			&cli.StringFlag{Name: "schema", Value: "fns"},
			&cli.StringFlag{Name: "table", Value: "masaddress"},
			&cli.BoolFlag{Name: "no-cache", Value: false},
			&cli.StringFlag{Name: "url", Value: fns.MasaddressURL},
		},
	}
}

func (t *Masaddress) Execute(c *cli.Context) error {
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
		func(obj *model.Masaddress, flush fns.FlushFunc) error {
			documentID := documentSeq.Add(1)

			address := []string{
				obj.Region,
				obj.District,
				obj.City,
				obj.Settlement,
				obj.House,
				obj.Building,
				obj.Apartment,
			}

			rows = append(rows, []any{documentID, strings.Join(address, ", "), obj.Count})

			return nil
		},
		func(flush fns.FlushFunc) error {
			if err := flush(rows, []string{"id", "address", "count"}, schema, table); err != nil {
				return fmt.Errorf("failed to flush data: %w", err)
			}

			return nil
		},
		schema, table); err != nil {
		return fmt.Errorf("failed to invoke: %w", err)
	}

	return nil
}
