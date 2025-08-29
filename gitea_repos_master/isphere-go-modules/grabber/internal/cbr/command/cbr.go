package command

import (
	"encoding/csv"
	"errors"
	"fmt"
	"io"
	"os"
	"sync"
	"sync/atomic"

	"git.i-sphere.ru/isphere-go-modules/grabber/internal/util"
	"github.com/jackc/pgx/v5"
	"github.com/schollz/progressbar/v3"
	"github.com/sirupsen/logrus"
	"github.com/urfave/cli/v2"
)

type Cbr struct {
}

func NewCbr() *Cbr {
	return &Cbr{}
}

func (t *Cbr) Describe() *cli.Command {
	return &cli.Command{
		Category: "cbr",
		Name:     "cbr",
		Action:   t.Execute,
		Usage:    "База данных утечки ЦБР",
		Flags: cli.FlagsByName{
			&cli.StringFlag{Name: "database", Required: true, EnvVars: []string{"ISPHERE_GRABBER_DATABASE"}},
			&cli.StringFlag{Name: "schema", Value: "cbr"},
			&cli.StringFlag{Name: "table", Value: "cbr"},
			&cli.StringFlag{Name: "table-title", Value: "titles"},
			&cli.StringFlag{Name: "table-reason", Value: "reasons"},
			&cli.StringFlag{Name: "filename", Value: "var/cbr.csv"},
		},
	}
}

func (t *Cbr) Execute(c *cli.Context) error {
	var (
		ctx          = c.Context
		filename     = c.String("filename")
		database     = c.String("database")
		schema       = c.String("schema")
		table        = c.String("table")
		titlesTable  = c.String("table-title")
		reasonsTable = c.String("table-reason")
	)

	f, err := os.Open(filename)
	if err != nil {
		return fmt.Errorf("failed to open file: %w", err)
	}

	defer func() {
		if err := f.Close(); err != nil {
			logrus.WithError(err).Error("failed to close file: %w", err)
		}
	}()

	r := csv.NewReader(f)
	r.LazyQuotes = true
	r.FieldsPerRecord = 3

	fileInfo, err := os.Stat(filename)
	if err != nil {
		return fmt.Errorf("failed to get file info: %w", err)
	}

	var (
		bar  = progressbar.DefaultBytes(fileInfo.Size(), filename)
		ch   = make(chan [3]string)
		done = make(chan any)
	)

	var (
		itemSeq   atomic.Uint32
		titleSeq  atomic.Uint32
		reasonSeq atomic.Uint32

		items   sync.Map
		titles  sync.Map
		reasons sync.Map
	)

	go func() {
		for row := range ch {
			itemID := itemSeq.Add(1)

			var titleID uint32

			if existsTitle, ok := titles.Load(row[1]); !ok {
				newTitleID := titleSeq.Add(1)

				titles.Store(row[1], &Title{
					ID:    newTitleID,
					Title: row[1],
				})

				titleID = newTitleID
			} else {
				titleID = existsTitle.(*Title).ID
			}

			var reasonID uint32

			if existsReason, ok := reasons.Load(row[2]); !ok {
				newReasonID := reasonSeq.Add(1)

				reasons.Store(row[2], &Reason{
					ID:    newReasonID,
					Title: row[2],
				})

				reasonID = newReasonID
			} else {
				reasonID = existsReason.(*Reason).ID
			}

			items.Store(itemID, &Item{
				ID:       itemID,
				TitleID:  titleID,
				ReasonID: reasonID,
				INN:      row[0],
			})
		}

		close(done)
	}()

	for {
		row, err := r.Read()
		if err != nil {
			if errors.Is(err, io.EOF) {
				break
			}

			return fmt.Errorf("failed to read csv: %w", err)
		}

		if err = bar.Set64(r.InputOffset()); err != nil {
			logrus.WithError(err).Error("failed to increment progress bar")
		}

		ch <- [3]string{row[0], row[1], row[2]}
	}

	if err = bar.Finish(); err != nil {
		logrus.WithError(err).Error("failed to finish progress bar")
	}

	close(ch)
	<-done

	var rows1 [][]any
	items.Range(func(key, value any) bool {
		rows1 = append(rows1, []any{
			value.(*Item).ID,
			value.(*Item).INN,
			value.(*Item).TitleID,
			value.(*Item).ReasonID,
		})

		return true
	})

	var rows2 [][]any
	titles.Range(func(key, value any) bool {
		rows2 = append(rows2, []any{
			value.(*Title).ID,
			value.(*Title).Title,
		})

		return true
	})

	var rows3 [][]any
	reasons.Range(func(key, value any) bool {
		rows3 = append(rows3, []any{
			value.(*Reason).ID,
			value.(*Reason).Title,
		})

		return true
	})

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

	if err = util.PgDisableTriggers(ctx, conn, schema, table, titlesTable, reasonsTable); err != nil {
		return fmt.Errorf("failed to disable triggers: %w", err)
	}

	defer func() {
		if err := util.PgEnableTriggers(ctx, conn, schema, table, titlesTable, reasonsTable); err != nil {
			logrus.WithError(err).Error("failed to enable triggers: %w", err)
		}
	}()

	if err = util.PgTruncate(ctx, conn, schema, table, titlesTable, reasonsTable); err != nil {
		return fmt.Errorf("failed to truncate tables: %w", err)
	}

	bar = progressbar.Default(3, "flushing data")

	if _, err = conn.CopyFrom(ctx, pgx.Identifier{schema, reasonsTable}, []string{"id", "title"}, pgx.CopyFromRows(rows3)); err != nil {
		return fmt.Errorf("failed to copy from reasons: %w", err)
	}

	_ = bar.Add(1)

	if _, err = conn.CopyFrom(ctx, pgx.Identifier{schema, titlesTable}, []string{"id", "title"}, pgx.CopyFromRows(rows2)); err != nil {
		return fmt.Errorf("failed to copy from titles: %w", err)
	}

	_ = bar.Add(1)

	if _, err = conn.CopyFrom(ctx, pgx.Identifier{schema, table}, []string{"id", "inn", "title_id", "reason_id"}, pgx.CopyFromRows(rows1)); err != nil {
		return fmt.Errorf("failed to copy from items: %w", err)
	}

	_ = bar.Add(1)
	_ = bar.Finish()

	return nil
}

type Item struct {
	ID       uint32
	TitleID  uint32
	ReasonID uint32
	INN      string
}

type Title struct {
	ID    uint32
	Title string
}

type Reason struct {
	ID    uint32
	Title string
}
