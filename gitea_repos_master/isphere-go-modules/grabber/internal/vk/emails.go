package vk

import (
	"encoding/csv"
	"errors"
	"fmt"
	"io"
	"os"
	"strconv"

	"git.i-sphere.ru/isphere-go-modules/grabber/internal/util"
	"github.com/jackc/pgx/v5"
	"github.com/schollz/progressbar/v3"
	"github.com/sirupsen/logrus"
	"github.com/urfave/cli/v2"
)

type Emails struct{}

func NewEmails() *Emails {
	return &Emails{}
}

func (t *Emails) Describe() *cli.Command {
	return &cli.Command{
		Category: "vk",
		Name:     "vk:email",
		Action:   t.Execute,
		Usage:    "База данных утечки VK (email)",
		Flags: cli.FlagsByName{
			&cli.StringFlag{Name: "database", Required: true, EnvVars: []string{"ISPHERE_GRABBER_DATABASE"}},
			&cli.StringFlag{Name: "schema", Value: "vk"},
			&cli.StringFlag{Name: "table", Value: "emails"},
			&cli.StringFlag{Name: "filename", Value: "var/vk_emails.csv"},
		},
	}
}

func (t *Emails) Execute(c *cli.Context) error {
	var (
		ctx      = c.Context
		filename = c.String("filename")
		database = c.String("database")
		schema   = c.String("schema")
		table    = c.String("table")
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
		ch   = make(chan *Email)
		done = make(chan any)
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

	if err = util.PgTruncate(ctx, conn, schema, table); err != nil {
		return fmt.Errorf("failed to truncate table: %w", err)
	}

	go func() {
		var rows [][]any
		for row := range ch {
			rows = append(rows, []any{
				row.ID,
				row.VkID,
				row.Email,
			})

			if len(rows) > 1_000_000 {
				if _, err = conn.CopyFrom(ctx, pgx.Identifier{schema, table}, []string{"id", "vk_id", "email"}, pgx.CopyFromRows(rows)); err != nil {
					logrus.WithError(err).Fatal("failed to copy from")
				}

				rows = make([][]any, 0)
			}
		}

		if len(rows) > 0 {
			if _, err = conn.CopyFrom(ctx, pgx.Identifier{schema, table}, []string{"id", "vk_id", "email"}, pgx.CopyFromRows(rows)); err != nil {
				logrus.WithError(err).Fatal("failed to copy from")
			}
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

		id, err := strconv.Atoi(row[0])
		if err != nil {
			return fmt.Errorf("failed to cast id: %w", err)
		}

		vkID, err := strconv.Atoi(row[1])
		if err != nil {
			return fmt.Errorf("failed to cast vkID: %w", err)
		}

		ch <- &Email{
			ID:    int64(id),
			VkID:  int64(vkID),
			Email: row[2],
		}
	}

	if err = bar.Finish(); err != nil {
		logrus.WithError(err).Error("failed to finish progress bar")
	}

	close(ch)
	<-done

	return nil
}

type Email struct {
	ID    int64
	VkID  int64
	Email string
}
