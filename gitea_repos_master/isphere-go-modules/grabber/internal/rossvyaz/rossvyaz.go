package rossvyaz

import (
	"crypto/md5"
	"crypto/tls"
	"encoding/hex"
	"errors"
	"fmt"
	"io"
	"net/http"
	"os"
	"path/filepath"

	"git.i-sphere.ru/isphere-go-modules/grabber/internal/client"
	"git.i-sphere.ru/isphere-go-modules/grabber/internal/contract"
	"github.com/dimchansky/utfbom"
	"github.com/jackc/pgx/v5"
	"github.com/jackc/pgx/v5/pgxpool"
	"github.com/jfyne/csvd"
	"github.com/sirupsen/logrus"
	"github.com/urfave/cli/v2"
	"golang.org/x/sync/errgroup"
)

var links = []string{
	"https://opendata.digital.gov.ru/downloads/ABC-3xx.csv",
	"https://opendata.digital.gov.ru/downloads/ABC-4xx.csv",
	"https://opendata.digital.gov.ru/downloads/ABC-8xx.csv",
	"https://opendata.digital.gov.ru/downloads/DEF-9xx.csv",
}

type Rossvyaz struct{}

func NewRossvyaz() *Rossvyaz {
	return &Rossvyaz{}
}

func (t *Rossvyaz) Describe() *cli.Command {
	return &cli.Command{
		Category: "rossvyaz",
		Name:     "rossvyaz",
		Action:   t.Execute,
		Usage:    "Открытые данные Россвязи по плану нумерации телефонных номеров",
		Flags: cli.FlagsByName{
			&cli.StringFlag{Name: "database", Required: true, EnvVars: []string{"ISPHERE_GRABBER_DATABASE"}},
			&cli.StringFlag{Name: "schema", Value: "rossvyaz"},
			&cli.StringFlag{Name: "table", Value: "rossvyaz"},
			&cli.StringFlag{Name: "hasura-endpoint", EnvVars: []string{"HASURA_ENDPOINT"}},
			&cli.StringFlag{Name: "hasura-admin-secret", EnvVars: []string{"HASURA_ADMIN_SECRET"}},
			&cli.BoolFlag{Name: "no-cache", Value: false},
		},
	}
}

func (t *Rossvyaz) Execute(c *cli.Context) error {
	var (
		ctx = c.Context

		databaseStr = c.String("database")
		schema      = c.String("schema")
		table       = c.String("table")

		hasuraEndpoint    = c.String("hasura-endpoint")
		hasuraAdminSecret = c.String("hasura-admin-secret")

		filenames          = make([]string, 0)
		filenamesChannel   = make(chan string, len(links))
		filenamesCompleted = make(chan any)

		wg errgroup.Group
	)

	hasura := client.NewHasura(hasuraEndpoint, hasuraAdminSecret)
	hasuraRepository := client.NewHasuraRepository(hasura)

	regions, err := hasuraRepository.FindRegions(ctx)
	if err != nil {
		return fmt.Errorf("failed to fetch regions: %w", err)
	}

	pool, err := pgxpool.New(ctx, databaseStr)
	if err != nil {
		return fmt.Errorf("failed to create pgxpool: %w", err)
	}

	defer pool.Close()

	go func() {
		for filename := range filenamesChannel {
			filenames = append(filenames, filename)
		}

		close(filenamesCompleted)
	}()

	for _, link := range links {
		link := link

		wg.Go(func() error {
			var (
				hash     = md5.Sum([]byte(link))
				key      = hex.EncodeToString(hash[:])
				filename = filepath.Join(contract.CacheDir, key)
			)

			defer func() {
				filenamesChannel <- filename
			}()

			if _, err := os.Stat(filename); err == nil && !c.Bool("no-cache") {
				logrus.WithField("filename", filename).Info("getting source from cache")

				return nil
			}

			f, err := os.OpenFile(filename, os.O_CREATE|os.O_RDWR, 0o0644)
			if err != nil {
				return fmt.Errorf("failed to open file: %w", err)
			}

			//goland:noinspection GoUnhandledErrorResult
			defer f.Close()

			req, err := http.NewRequestWithContext(ctx, http.MethodGet, link, http.NoBody)
			if err != nil {
				return fmt.Errorf("failed to create request: %w", err)
			}

			http.DefaultTransport.(*http.Transport).TLSClientConfig = &tls.Config{
				// @todo разобраться, как добавить russian_trusted в alpine
				InsecureSkipVerify: true,
			}

			resp, err := http.DefaultClient.Do(req)
			if err != nil {
				return fmt.Errorf("failed to send request: %w", err)
			}

			//goland:noinspection GoUnhandledErrorResult
			defer resp.Body.Close()

			logrus.WithField("link", link).Info("downloading source")

			defer func(resp *http.Response, filename, link string) {
				logrus.WithFields(logrus.Fields{
					"link":        link,
					"status_code": resp.StatusCode,
					"filename":    filename,
				}).Info("source downloaded")
			}(resp, filename, link)

			if resp.StatusCode != http.StatusOK {
				return fmt.Errorf("unexpected status code: %d", resp.StatusCode)
			}

			if _, err := io.Copy(f, resp.Body); err != nil {
				return fmt.Errorf("failed to copy response to cache file: %w", err)
			}

			return nil
		})
	}

	if err = wg.Wait(); err != nil {
		return fmt.Errorf("failed to prepare files: %w", err)
	}

	close(filenamesChannel)

	<-filenamesCompleted

	var (
		itemsChannel   = make(chan *Item)
		itemsCompleted = make(chan any)
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

		var (
			id   int
			rows [][]any
		)

		for item := range itemsChannel {
			var regions []string
			if item.Region1 != "" {
				regions = append(regions, item.Region1)
			}

			if item.Region2 != "" {
				regions = append(regions, item.Region2)
			}

			if item.Region3 != "" {
				regions = append(regions, item.Region3)
			}

			rows = append(rows, []any{
				id,
				fmt.Sprintf("%d", item.ABCDEF),
				fmt.Sprintf("%d", item.PhonePoolStart),
				fmt.Sprintf("%d", item.PhonePoolEnd),
				fmt.Sprintf("%d", item.PhonePoolSize),
				item.Operator,
				item.PhoneRegionName,
				regions,
				item.RegionCode,
			})

			id++
		}

		columns := []string{
			"id",
			"abcdef",
			"phone_poolstart",
			"phone_poolend",
			"phone_poolsize",
			"operator",
			"phone_regionname",
			"regions",
			"regioncode",
		}

		if _, err = tx.CopyFrom(ctx, pgx.Identifier{schema, table}, columns, pgx.CopyFromRows(rows)); err != nil {
			logrus.WithError(err).Fatal("failed to copy from")
		}

		if err = tx.Commit(ctx); err != nil {
			logrus.WithError(err).Fatal("failed to commit")
		}

		close(itemsCompleted)
	}()

	for _, filename := range filenames {
		filename := filename

		wg.Go(func() error {
			f, err := os.Open(filename)
			if err != nil {
				return fmt.Errorf("failed to open file: %w", err)
			}

			//goland:noinspection GoUnhandledErrorResult
			defer f.Close()

			reader := csvd.NewReader(utfbom.SkipOnly(f))
			reader.FieldsPerRecord = 7
			reader.LazyQuotes = true

			_, _ = reader.Read() // skip first line

			for {
				row, err := reader.Read()
				if err != nil {
					if errors.Is(err, io.EOF) {
						break
					}

					return fmt.Errorf("failed to read: %w", err)
				}

				item, err := NewItemUsingRow(row, regions)
				if err != nil {
					logrus.WithError(err).Warn("failed to parse item")

					continue
				}

				itemsChannel <- item
			}

			return nil
		})
	}

	if err = wg.Wait(); err != nil {
		return fmt.Errorf("failed to merge files: %w", err)
	}

	close(itemsChannel)

	<-itemsCompleted

	return nil
}
