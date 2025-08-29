package util

import (
	"context"
	"crypto/md5"
	"encoding/hex"
	"fmt"
	"os"
	"path"
	"path/filepath"
	"time"

	"git.i-sphere.ru/isphere-go-modules/grabber/internal/contract"
	"github.com/jackc/pgx/v5"
	"github.com/jackc/pgx/v5/pgxpool"
	"github.com/sirupsen/logrus"
)

func PgConnect(ctx context.Context, database string) (*pgxpool.Pool, error) {
	logrus.Debug("connecting database")

	cfg, err := pgxpool.ParseConfig(database)
	if err != nil {
		return nil, fmt.Errorf("failed to parge pg config: %w", err)
	}

	pool, err := pgxpool.NewWithConfig(ctx, cfg)
	if err != nil {
		return nil, fmt.Errorf("failed to create pgxpool: %w", err)
	}

	return pool, err
}

func PgTransactional(ctx context.Context, pool *pgxpool.Pool, fn func(tx pgx.Tx) error) error {
	conn, err := pool.Acquire(ctx)
	if err != nil {
		return fmt.Errorf("failed to acquire conn pool: %w", err)
	}

	defer conn.Release()

	tx, err := conn.Begin(ctx)
	if err != nil {
		return fmt.Errorf("failed to begin transaction: %w", err)
	}

	if err = fn(tx); err != nil {
		if err1 := tx.Rollback(ctx); err1 != nil {
			logrus.WithError(err1).Error("failed to rollback transaction: %w", err1)
		}

		return fmt.Errorf("failed to execute closure: %w", err)
	}

	if err = tx.Commit(ctx); err != nil {
		return fmt.Errorf("failed to commit transaction: %w", err)
	}

	return nil
}

func PgTruncate(ctx context.Context, conn *pgxpool.Conn, schema string, tables ...string) error {
	for _, table := range tables {
		logrus.WithField("schema", schema).WithField("table", table).Debug("truncating table")

		if _, err := conn.Exec(ctx, fmt.Sprintf("truncate table %s.%s cascade", schema, table)); err != nil {
			return fmt.Errorf("failed to truncate table: %w", err)
		}
	}

	return nil
}

func PgDisableTriggers(ctx context.Context, conn *pgxpool.Conn, schema string, tables ...string) error {
	for _, table := range tables {
		logrus.WithField("schema", schema).WithField("table", table).Debug("disabling all triggers")

		if _, err := conn.Exec(ctx, fmt.Sprintf("alter table %s.%s disable trigger all", schema, table)); err != nil {
			return fmt.Errorf("failed to disable all triggers: %w", err)
		}
	}

	return nil
}

func PgEnableTriggers(ctx context.Context, conn *pgxpool.Conn, schema string, tables ...string) error {
	for _, table := range tables {
		logrus.WithField("schema", schema).WithField("table", table).Debug("enabling all triggers")

		if _, err := conn.Exec(ctx, fmt.Sprintf("alter table %s.%s enable trigger all", schema, table)); err != nil {
			return fmt.Errorf("failed to enable all triggers: %w", err)
		}
	}

	return nil
}

func PgNullable(value any) any {
	switch v := value.(type) {
	case string:
		if v == "" {
			return nil
		}
		return &v
	default:
		return &v
	}
}

func PgCache(ctx context.Context, url string, noCache bool) (string, error) {
	logrus.Debug("creating cache")

	var (
		hash     = md5.Sum([]byte(url))
		key      = hex.EncodeToString(hash[:])
		filename = filepath.Join(contract.CacheDir, key) + DefaultExt(path.Ext(url), ".html")
	)

	if _, err := os.Stat(filename); err == nil && !noCache {
		logrus.WithFields(logrus.Fields{
			"target": filename,
			"source": url,
		}).Info("getting source from cache")
	} else {
		if err := Download(ctx, filename, url); err != nil {
			_ = os.Remove(filename)

			return "", fmt.Errorf("failed to download source: %w", err)
		}
	}

	return filename, nil
}

func TryPgCache(ctx context.Context, url string, noCache bool) (string, error) {
	for i := 0; i < 10; i++ {
		result, err := PgCache(ctx, url, noCache)
		if err == nil {
			return result, nil
		}

		logrus.WithField("url", url).Warn("failed to get source, retries again")
		time.Sleep(2 * time.Second)
	}

	return "", fmt.Errorf("retry times exceed")
}

func DefaultExt(url string, defaultExt string) string {
	if ext := path.Ext(url); ext != "" {
		return ext
	}

	return defaultExt
}

func Hash(str string) string {
	hash := md5.Sum([]byte(str))

	return hex.EncodeToString(hash[:])
}
