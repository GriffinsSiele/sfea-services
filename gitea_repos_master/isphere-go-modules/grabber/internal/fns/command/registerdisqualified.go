package command

import (
	"fmt"
	"time"

	"git.i-sphere.ru/isphere-go-modules/grabber/internal/fns"
	"git.i-sphere.ru/isphere-go-modules/grabber/internal/fns/model"
	"git.i-sphere.ru/isphere-go-modules/grabber/internal/util"
	"github.com/urfave/cli/v2"
)

type Registerdisqualified struct{}

func NewRegisterdisqualified() *Registerdisqualified {
	return &Registerdisqualified{}
}

func (t *Registerdisqualified) Describe() *cli.Command {
	return &cli.Command{
		Category: "fns",
		Name:     "fns:registerdisqualified",
		Action:   t.Execute,
		Usage:    "Реестр дисквалифицированных лиц",
		Flags: cli.FlagsByName{
			&cli.StringFlag{Name: "database", Required: true, EnvVars: []string{"ISPHERE_GRABBER_DATABASE"}},
			&cli.StringFlag{Name: "schema", Value: "fns"},
			&cli.StringFlag{Name: "table", Value: "registerdisqualified"},
			&cli.BoolFlag{Name: "no-cache", Value: false},
			&cli.StringFlag{Name: "url", Value: fns.RegisterdisqualifiedURL},
		},
	}
}

func (t *Registerdisqualified) Execute(c *cli.Context) error {
	var (
		ctx      = c.Context
		database = c.String("database")
		schema   = c.String("schema")
		table    = c.String("table")
		noCache  = c.Bool("no-cache")
		url      = c.String("url")
	)

	var rows [][]any

	if err := fns.Invoke(ctx, database, url, noCache,
		func(obj *model.Registerdisqualified, flush fns.FlushFunc) error {
			rows = append(rows, []any{
				obj.ID,
				util.PgNullable(obj.FullName),
				obj.Birthday.Format(time.DateOnly),
				util.PgNullable(obj.Birthplace),
				util.PgNullable(obj.OrganizationName),
				obj.OrganizationINN,
				util.PgNullable(obj.OrganizationPosition),
				util.PgNullable(obj.Reason),
				util.PgNullable(obj.ReasonIssuer),
				util.PgNullable(obj.JudgeName),
				util.PgNullable(obj.JudgePosition),
				obj.DisqualificationPeriod.String(),
				obj.StartAt.Format(time.DateOnly),
				obj.ExpiredAt.Format(time.DateOnly),
			})

			return nil
		},
		func(flush fns.FlushFunc) error {
			if err := flush(rows, []string{"id", "full_name", "birthday", "birthplace", "organization_name", "organization_inn", "organization_position", "reason", "reason_issuer", "judge", "judge_position", "disqualification_period", "start_at", "end_at"}, schema, table); err != nil {
				return fmt.Errorf("failed to flush data: %w", err)
			}

			return nil
		},
		schema, table); err != nil {
		return fmt.Errorf("failed to invoke: %w", err)
	}

	return nil
}
