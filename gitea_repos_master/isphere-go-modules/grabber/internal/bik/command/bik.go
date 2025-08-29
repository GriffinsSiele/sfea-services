package command

import (
	"bufio"
	"encoding/xml"
	"errors"
	"fmt"
	"io"
	"os"
	"sync/atomic"

	"git.i-sphere.ru/isphere-go-modules/grabber/internal/util"
	"github.com/jackc/pgx/v5"
	"github.com/sirupsen/logrus"
	"github.com/urfave/cli/v2"
)

type Bik struct{}

func NewBik() *Bik {
	return &Bik{}
}

func (t *Bik) Describe() *cli.Command {
	return &cli.Command{
		Category: "bik",
		Name:     "bik",
		Action:   t.Execute,
		Usage:    "База данных БИК",
		Flags: cli.FlagsByName{
			&cli.StringFlag{Name: "database", Required: true, EnvVars: []string{"ISPHERE_GRABBER_DATABASE"}},
			&cli.StringFlag{Name: "schema", Value: "bik"},
			&cli.StringFlag{Name: "table", Value: "bik"},
			&cli.BoolFlag{Name: "no-cache"},
			&cli.StringFlag{Name: "url", Value: "https://bik-info.ru/base/base.xml"},
		},
	}
}

func (t *Bik) Execute(c *cli.Context) error {
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
		return fmt.Errorf("failed to download url: %w", err)
	}

	f, err := os.Open(filename)
	if err != nil {
		return fmt.Errorf("failed to open file: %w", err)
	}

	defer func() {
		if err := f.Close(); err != nil {
			logrus.WithError(err).Error("failed to close file")
		}
	}()

	var (
		scanner = bufio.NewScanner(f)
		rows    [][]any
		idSeq   atomic.Uint64
	)

	for scanner.Scan() {
		line := scanner.Bytes()

		var item Item
		if err := xml.Unmarshal(line, &item); err != nil {
			logrus.WithError(err).Warn("failed to scan line")

			continue
		}

		id := idSeq.Add(1)

		if n, a := item.Name, item.Alias; n == a {
			item.Alias = ""
		}

		rows = append(rows, []any{
			id,
			unsafe(item.Identity),
			unsafe(item.CorrespondingAccount),
			unsafe(item.Name),
			unsafe(item.Alias),
			unsafe(item.PostIndex),
			unsafe(item.City),
			unsafe(item.Address),
			unsafe(item.Phone),
			unsafe(item.OKATO),
			unsafe(item.OKPO),
			unsafe(item.RegistrationNumber),
			unsafe(item.Timeframe),
			unsafe(item.CreatedAt),
			unsafe(item.UpdatedAt),
		})
	}

	if err = scanner.Err(); err != nil {
		if !errors.Is(err, io.EOF) {
			return fmt.Errorf("failed to scan file: %w", err)
		}
	}

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

	if err = util.PgTruncate(ctx, conn, schema, table); err != nil {
		return fmt.Errorf("failed to truncate table: %w", err)
	}

	if _, err := conn.CopyFrom(ctx, pgx.Identifier{schema, table},
		[]string{"id", "identity", "corresponding_account", "name", "alias", "post_index", "city", "address",
			"phone", "okato", "okpo", "registration_number", "timeframe", "created_at", "updated_at"},
		pgx.CopyFromRows(rows)); err != nil {
		return fmt.Errorf("failed to copy data: %w", err)
	}

	return nil
}

func unsafe(v string) any {
	if v == "" {
		return nil
	}

	return v
}

// ---

type Item struct {
	XMLName              xml.Name `xml:"bik"`
	Identity             string   `xml:"bik,attr,omitempty"`
	CorrespondingAccount string   `xml:"ks,attr,omitempty"`
	Name                 string   `xml:"name,attr,omitempty"`
	Alias                string   `xml:"namemini,attr,omitempty"`
	PostIndex            string   `xml:"index,attr,omitempty"`
	City                 string   `xml:"city,attr,omitempty"`
	Address              string   `xml:"address,attr,omitempty"`
	Phone                string   `xml:"phone,attr,omitempty"`
	OKATO                string   `xml:"okato,attr,omitempty"`
	OKPO                 string   `xml:"okpo,attr,omitempty"`
	RegistrationNumber   string   `xml:"regnum,attr,omitempty"`
	Timeframe            string   `xml:"srok,attr,omitempty"`
	CreatedAt            string   `xml:"dateadd,attr,omitempty"`
	UpdatedAt            string   `xml:"datechange,attr,omitempty"`
}
