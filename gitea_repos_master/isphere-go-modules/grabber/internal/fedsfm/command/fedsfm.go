package command

import (
	"crypto/md5"
	"encoding/hex"
	"fmt"
	"os"
	"path/filepath"
	"regexp"
	"strings"
	"sync/atomic"
	"time"

	"git.i-sphere.ru/isphere-go-modules/grabber/internal/contract"
	"git.i-sphere.ru/isphere-go-modules/grabber/internal/fedsfm/model"
	"git.i-sphere.ru/isphere-go-modules/grabber/internal/util"
	"github.com/antchfx/htmlquery"
	"github.com/jackc/pgx/v5"
	"github.com/jackc/pgx/v5/pgxpool"
	"github.com/oriser/regroup"
	"github.com/sirupsen/logrus"
	"github.com/urfave/cli/v2"
)

const DefaultFedsfmURL = "https://www.fedsfm.ru/documents/terrorists-catalog-portal-act"

type Fedsfm struct{}

func NewFedsfm() *Fedsfm {
	return &Fedsfm{}
}

func (t *Fedsfm) Describe() *cli.Command {
	return &cli.Command{
		Category: "fedsfm",
		Name:     "fedsfm",
		Action:   t.Execute,
		Usage:    "Перечень террористов и экстремистов",
		Flags: cli.FlagsByName{
			&cli.StringFlag{Name: "database", Required: true, EnvVars: []string{"ISPHERE_GRABBER_DATABASE"}},
			&cli.StringFlag{Name: "schema", Value: "fedsfm"},
			&cli.StringFlag{Name: "table", Value: "terrorists"},
			&cli.BoolFlag{Name: "no-cache", Value: false},
			&cli.StringFlag{Name: "url", Value: DefaultFedsfmURL},
		},
	}
}

func (t *Fedsfm) Execute(c *cli.Context) error {
	var (
		ctx = c.Context

		databaseStr = c.String("database")
		schema      = c.String("schema")
		table       = c.String("table")
		link        = c.String("url")
	)

	pool, err := pgxpool.New(ctx, databaseStr)
	if err != nil {
		return fmt.Errorf("failed to create pgxpool: %w", err)
	}

	defer pool.Close()

	var (
		hash     = md5.Sum([]byte(link))
		key      = hex.EncodeToString(hash[:])
		filename = filepath.Join(contract.CacheDir, key)
	)

	if _, err = os.Stat(filename); err == nil && !c.Bool("no-cache") {
		logrus.WithField("filename", filename).Info("getting source from cache")
	} else {
		if err = util.Download(ctx, filename, link); err != nil {
			return fmt.Errorf("failed to download source: %w", err)
		}
	}

	doc, err := htmlquery.LoadDoc(filename)
	if err != nil {
		return fmt.Errorf("failed to load document: %w", err)
	}

	items, err := htmlquery.QueryAll(doc, "//div[contains(@id, 'russianFL')]//ol[contains(@class, 'terrorist-list')]/li")
	if err != nil {
		return fmt.Errorf("failed to xpath query: %w", err)
	}

	if len(items) < 5_000 {
		return fmt.Errorf("found records count less than control (5000): %d", len(items))
	}

	var (
		reVariant1 = regroup.MustCompile(`(?m)^\d+\.\s(?P<names>.+)?,\s(?P<birthday>\d{2}\.\d{2}\.\d{4})\sг.р.\s,\s(?P<birthplace>.+);`)
		reVariant2 = regroup.MustCompile(`(?m)^\d+\.\s(?P<names>.+)?,\s(?P<birthday>\d{2}\.\d{2}\.\d{4})\sг.р.\s;`)
		reVariant3 = regroup.MustCompile(`(?m)^\d+\.\s(?P<names>.+)?,\s,\s(?P<birthplace>.+);`)
		reLatin    = regexp.MustCompile(`(?i)[A-Z]+`)
		reSpace    = regexp.MustCompile(`\s+`)
		ch         = make(chan *model.Fedsfm)
		done       = make(chan any)
		idSeq      atomic.Uint64
	)

	go func() {
		conn, err := pool.Acquire(ctx)
		if err != nil {
			logrus.WithError(err).Fatal("failed to acquire database connection")
		}

		defer conn.Release()

		tx, err := conn.Begin(ctx)
		if err != nil {
			logrus.WithError(err).Fatal("failed to open database transaction")
		}

		//goland:noinspection GoUnhandledErrorResult
		defer tx.Rollback(ctx) //nolint:errcheck

		if _, err = tx.Exec(ctx, fmt.Sprintf("truncate table %s.%s", schema, table)); err != nil {
			logrus.WithError(err).Fatalf("failed to execute truncate table")
		}

		var rows [][]any

		for item := range ch {
			id := idSeq.Add(1)

			rows = append(rows, []any{
				id,
				item.Surname,
				item.Name,
				nonEmptyStr(item.Patronymic),
				item.Birthday,
				nonEmptyStr(item.Birthplace),
			})
		}

		columns := []string{
			"id",
			"surname",
			"name",
			"patronymic",
			"birthday",
			"birthplace",
		}

		if _, err = tx.CopyFrom(ctx, pgx.Identifier{schema, table}, columns, pgx.CopyFromRows(rows)); err != nil {
			logrus.WithError(err).Fatal("failed to copy from")
		}

		if err = tx.Commit(ctx); err != nil {
			logrus.WithError(err).Fatal("failed to commit")
		}

		close(done)
	}()

	for _, item := range items {
		value := htmlquery.InnerText(item)
		value = strings.ReplaceAll(value, "\n", "")

		match, err := reVariant1.Groups(value)
		if err != nil {
			if match, err = reVariant2.Groups(value); err != nil {
				if match, err = reVariant3.Groups(value); err != nil {
					return fmt.Errorf("failed to parse string: %s: %w", value, err)
				}
			}
		}

		var (
			birthday   *time.Time
			birthplace *string
		)

		if tmp1, ok := match["birthday"]; ok {
			if tmp2, err := time.Parse("02.01.2006", tmp1); err != nil {
				return fmt.Errorf("failed to parse birthday: %w", err)
			} else {
				birthday = &tmp2
			}
		}

		if tmp, ok := match["birthplace"]; ok {
			birthplace = &tmp
		}

		names := strings.FieldsFunc(match["names"], func(r rune) bool {
			return r == ',' || r == ';'
		})

		for _, name := range names {
			name = strings.Trim(name, " *()")
			name = reSpace.ReplaceAllString(name, " ")

			if reLatin.MatchString(name) {
				logrus.WithField("name", name).Warn("skip transliterate name")

				continue
			}

			components := strings.Split(name, " ")
			record := &model.Fedsfm{
				Surname:    components[0],
				Name:       components[1],
				Birthday:   birthday,
				Birthplace: birthplace,
			}

			if len(components) > 2 {
				record.Patronymic = &components[2]
			}

			ch <- record
		}
	}

	close(ch)

	<-done

	return nil
}

func nonEmptyStr(v *string) *string {
	if v == nil {
		return nil
	}

	if *v == "" {
		return nil
	}

	return v
}
