package fmsdb

import (
	"bytes"
	"compress/bzip2"
	"crypto/md5"
	"encoding/csv"
	"encoding/hex"
	"errors"
	"fmt"
	"io"
	"os"
	"path/filepath"
	"strconv"

	"git.i-sphere.ru/isphere-go-modules/grabber/internal/contract"
	"git.i-sphere.ru/isphere-go-modules/grabber/internal/util"
	"github.com/jackc/pgx/v5"
	"github.com/jackc/pgx/v5/pgxpool"
	"github.com/sirupsen/logrus"
	"github.com/urfave/cli/v2"
)

type FMSDB struct {
	Parsed map[string]bool
}

func NewFsin() *FMSDB {
	return &FMSDB{
		Parsed: make(map[string]bool),
	}
}

func (t *FMSDB) Describe() *cli.Command {
	return &cli.Command{
		Category: "fmsdb",
		Name:     "fmsdb",
		Action:   t.Execute,
		Usage:    "База данных недействительных паспортов",
		Flags: cli.FlagsByName{
			&cli.StringFlag{Name: "database", Required: true, EnvVars: []string{"ISPHERE_GRABBER_DATABASE"}},
			&cli.StringFlag{Name: "schema", Value: "fmsdb"},
			&cli.StringFlag{Name: "table", Value: "invalid_passports"},
			&cli.BoolFlag{Name: "no-cache"},
			&cli.StringFlag{Name: "url", Value: "https://maven.hflabs.ru:443/artifactory/ext-releases-public/ru/mvd/passports-expired/20230621/passports-expired-20230621.csv.bz2"},
			&cli.StringFlag{Name: "bucket", Value: "c2e09116-grabber"},
		},
	}
}

func (t *FMSDB) Execute(c *cli.Context) error {
	var (
		ctx      = c.Context
		pageURL  = c.String("url")
		noCache  = c.Bool("no-cache")
		database = c.String("database")
		schema   = c.String("schema")
		table    = c.String("table")
	)

	pool, err := pgxpool.New(ctx, database)
	if err != nil {
		return fmt.Errorf("failed to connect to database: %w", err)
	}
	defer pool.Close()

	var (
		hash     = md5.Sum([]byte(pageURL))
		key      = hex.EncodeToString(hash[:])
		filename = filepath.Join(contract.CacheDir, key)
	)

	if _, err = os.Stat(filename); err == nil && !noCache {
		logrus.WithField("filename", filename).Info("getting file from cache")
	} else if err = util.Download(ctx, filename, pageURL); err != nil {
		return fmt.Errorf("failed to download file: %w", err)
	}

	f, err := os.Open(filename)
	if err != nil {
		return fmt.Errorf("failed to open file: %w", err)
	}
	defer f.Close()

	var firstBytes [6]byte
	if n, err := f.Read(firstBytes[:]); err != nil || n != 6 {
		return fmt.Errorf("failed to read file: %w", err)
	}
	f.Seek(0, 0)

	var reader *csv.Reader

	switch {
	case t.IsBZ2(firstBytes):
		logrus.WithField("filename", filename).Info("reading bz2 file")
		if reader, err = t.ReadBZ2(f); err != nil {
			return fmt.Errorf("failed to read bz2 file: %w", err)
		}
	default:
		return fmt.Errorf("unable to detect file format: %v", firstBytes)
	}

	if _, err = reader.Read(); err != nil { // skip header
		return fmt.Errorf("failed to read file: %w", err)
	}

	uniqueMap := make(map[string]bool)
	rows := make([][]any, 0)
	for {
		row, err := reader.Read()
		if err != nil {
			if errors.Is(err, io.EOF) {
				break
			}
			return fmt.Errorf("failed to read file: %w", err)
		}

		if len(row) != 2 {
			logrus.WithField("row", row).Warn("unexpected row length")
			continue
		}

		seriesNum, err := strconv.Atoi(row[0])
		if err != nil {
			logrus.WithField("row", row).WithError(err).Warn("failed to parse series")
			continue
		}
		numberNum, err := strconv.Atoi(row[1])
		if err != nil {
			logrus.WithField("row", row).WithError(err).Warn("failed to parse number")
			continue
		}
		if seriesNum == 0 || numberNum == 0 {
			logrus.WithField("row", row).Warn("invalid series or number")
			continue
		}
		uniqueKey := fmt.Sprintf("%d_%d", seriesNum, numberNum)
		if _, ok := uniqueMap[uniqueKey]; ok {
			logrus.WithField("row", row).WithField("uniqueKey", uniqueKey).Warn("duplicate unique key")
			continue
		}

		rows = append(rows, []any{seriesNum, numberNum})
		uniqueMap[uniqueKey] = true
	}

	conn, err := pool.Acquire(ctx)
	if err != nil {
		return fmt.Errorf("failed to acquire connection: %w", err)
	}
	defer conn.Release()

	tx, err := conn.Begin(ctx)
	if err != nil {
		return fmt.Errorf("failed to begin transaction: %w", err)
	}
	defer tx.Rollback(ctx)

	if _, err = tx.Exec(ctx, fmt.Sprintf("truncate table %s.%s", schema, table)); err != nil {
		return fmt.Errorf("failed to truncate table: %w", err)
	}

	logrus.WithField("rows", len(rows)).Info("writing rows")
	columns := []string{"series", "number"}
	if _, err = tx.CopyFrom(ctx, pgx.Identifier{schema, table}, columns, pgx.CopyFromRows(rows)); err != nil {
		return fmt.Errorf("failed to copy from rows: %w", err)
	}
	if err = tx.Commit(ctx); err != nil {
		return fmt.Errorf("failed to commit transaction: %w", err)
	}

	return nil
}

// IsBZ2 https://github.com/file/file/blob/master/magic/Magdir/compress
func (t *FMSDB) IsBZ2(firstBytes [6]byte) bool {
	return bytes.Compare(firstBytes[0:3], []byte{0x42, 0x5a, 0x68}) == 0
}

func (t *FMSDB) ReadBZ2(f *os.File) (*csv.Reader, error) {
	reader := bzip2.NewReader(f)
	return csv.NewReader(reader), nil
}
