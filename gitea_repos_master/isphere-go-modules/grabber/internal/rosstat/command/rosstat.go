package command

import (
	"encoding/csv"
	"errors"
	"fmt"
	"io"
	"os"
	"strings"

	"git.i-sphere.ru/isphere-go-modules/grabber/internal/util"
	"github.com/antchfx/htmlquery"
	"github.com/jackc/pgx/v5"
	"github.com/sirupsen/logrus"
	"github.com/urfave/cli/v2"
	"golang.org/x/text/encoding/charmap"
)

type RosStat struct{}

func NewRosStat() *RosStat {
	return &RosStat{}
}

func (t *RosStat) Describe() *cli.Command {
	return &cli.Command{
		Category: "rosstat",
		Name:     "rosstat:activities",
		Action:   t.Execute,
		Usage:    "Общероссийский классификатор видов экономической деятельности (ОКВЭД2)",
		Flags: cli.FlagsByName{
			&cli.StringFlag{Name: "database", Required: true, EnvVars: []string{"ISPHERE_GRABBER_DATABASE"}},
			&cli.StringFlag{Name: "schema", Value: "rosstat"},
			&cli.StringFlag{Name: "table", Value: "activities"},
			&cli.BoolFlag{Name: "no-cache", Value: false},
			&cli.StringFlag{Name: "url", Value: "https://rosstat.gov.ru/opendata/7708234640-okved2"},
		},
	}
}

func (t *RosStat) Execute(c *cli.Context) error {
	var (
		ctx      = c.Context
		database = c.String("database")
		schema   = c.String("schema")
		table    = c.String("table")
		noCache  = c.Bool("no-cache")
		url      = c.String("url")
	)
	filename, err := util.PgCache(ctx, url, noCache)
	if err != nil {
		return fmt.Errorf("failed to fetch source: %w", err)
	}

	document, err := htmlquery.LoadDoc(filename)
	if err != nil {
		return fmt.Errorf("failed to load document: %w", err)
	}

	aNode, err := htmlquery.Query(document, "//td[contains(text(), 'Гиперссылка (URL) на набор')]/following-sibling::td/a")
	if err != nil {
		return fmt.Errorf("failed to find link")
	}

	sourceURL := htmlquery.SelectAttr(aNode, "href")
	if sourceURL == "" {
		return errors.New("link is empty")
	}

	source, err := util.PgCache(ctx, sourceURL, noCache)
	if err != nil {
		return fmt.Errorf("failed to fetch source data: %w", err)
	}

	f, err := os.Open(source)
	if err != nil {
		return fmt.Errorf("failed to open local source: %w", err)
	}

	defer func() {
		if err := f.Close(); err != nil {
			logrus.WithError(err).Error("failed to close local source")
		}
	}()

	fh := csv.NewReader(f)
	fh.LazyQuotes = true
	fh.Comma = ';'

	var (
		encoder = charmap.Windows1251.NewDecoder()
		rows    [][]any
	)

	for {
		row, err := fh.Read()
		if err != nil {
			if errors.Is(err, io.EOF) {
				break
			}

			return fmt.Errorf("failed to read csv: %w", err)
		}

		s, err := encoder.String(row[2])
		if err != nil {
			return fmt.Errorf("encoding err: %w", err)
		}

		s = strings.TrimSpace(s)
		if row[1] = strings.TrimSpace(row[1]); row[1] == "" {
			continue
		}

		rows = append(rows, []any{
			row[0],
			row[1],
			s,
		})
	}

	pool, err := util.PgConnect(ctx, database)
	if err != nil {
		return fmt.Errorf("failed to pg pool: %w", err)
	}

	defer pool.Close()

	if err = util.PgTransactional(ctx, pool, func(tx pgx.Tx) error {
		if _, err := tx.Exec(ctx, fmt.Sprintf("truncate table %s.%s", schema, table)); err != nil {
			return fmt.Errorf("failed to truncate")
		}

		if _, err = tx.CopyFrom(ctx, pgx.Identifier{schema, table}, []string{"section", "code", "title"}, pgx.CopyFromRows(rows)); err != nil {
			return fmt.Errorf("failed to copy: %w", err)
		}

		return nil
	}); err != nil {
		return fmt.Errorf("failed to upload data: %w", err)
	}

	return nil
}

type Item struct {
	Section string
	Code    string
	Title   string
}
