package announcements

import (
	"archive/tar"
	"bufio"
	"bytes"
	"compress/gzip"
	"encoding/json"
	"errors"
	"fmt"
	"io"
	"net/url"
	"os"
	"path/filepath"
	"runtime"
	"strconv"
	"strings"
	"sync/atomic"
	"time"

	"git.i-sphere.ru/isphere-go-modules/grabber/internal/util"
	"github.com/jackc/pgx/v5"
	"github.com/schollz/progressbar/v3"
	"github.com/sirupsen/logrus"
	"github.com/ttacon/libphonenumber"
	"github.com/urfave/cli/v2"
	"golang.org/x/sync/errgroup"
)

type Announcements struct{}

func NewAnnouncements() *Announcements {
	return &Announcements{}
}

func (t *Announcements) Describe() *cli.Command {
	return &cli.Command{
		Category: "announcements",
		Name:     "announcements",
		Action:   t.Execute,
		Usage:    "База данных announcements",
		Flags: cli.FlagsByName{
			&cli.StringFlag{Name: "database", Required: true, EnvVars: []string{"ISPHERE_GRABBER_DATABASE"}},
			&cli.StringFlag{Name: "schema", Value: "announcements"},
			&cli.StringFlag{Name: "table", Value: "announcements"},
			&cli.StringFlag{Name: "folder", Value: "var/announcements-legacy/opt/announcements"},
		},
	}
}

func (t *Announcements) Execute(c *cli.Context) error {
	var (
		ctx      = c.Context
		database = c.String("database")
		schema   = c.String("schema")
		table    = c.String("table")
		folder   = c.String("folder")
	)

	entries, err := os.ReadDir(folder)
	if err != nil {
		return fmt.Errorf("failed to read dir: %w", err)
	}

	var (
		announcementsChannel = make(chan *Announcement)
		announcementsDone    = make(chan bool, 1)
		idSeq                atomic.Int64
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

	var rows [][]any

	columns := []string{
		"id",
		"url",
		"category",
		"subcategory",
		"region",
		"city",
		"subway",
		"address",
		"status",
		"company",
		"seller",
		"contact",
		"phone",
		"operator",
		"service_regions",
		"published_at",
		"title",
		"parameters",
		"text",
		"price",
		"latitude",
		"longitude",
	}

	go func() {
		defer close(announcementsDone)

		for announcement := range announcementsChannel {
			var (
				id  = idSeq.Add(1)
				row = []any{
					id,  // 0,id
					nil, // 1,url
					nil, // 2,category
					nil, // 3,subcategory
					nil, // 4,region
					nil, // 5,city
					nil, // 6,subway
					nil, // 7,address
					nil, // 8,status
					nil, // 9,company
					nil, // 10,seller
					nil, // 11,contact
					nil, // 12,phone
					nil, // 13,operator
					nil, // 14,service_regions
					nil, // 15,published_at
					nil, // 16,title
					nil, // 17,parameters
					nil, // 18,text
					nil, // 19,price
					nil, // 20,latitude
					nil, // 21,longitude
				}
			)

			switch announcement.Scheme {
			case 0:
				row[1] = resolveURL(resolve(announcement.Data, 0))
				row[2] = resolveString(resolve(announcement.Data, 1))
				row[3] = resolveString(resolve(announcement.Data, 2))
				row[4] = resolveString(resolve(announcement.Data, 3))
				row[5] = resolveString(resolve(announcement.Data, 4))
				row[6] = resolveString(resolve(announcement.Data, 5))
				row[7] = resolveString(resolve(announcement.Data, 6))
				row[8] = resolveString(resolve(announcement.Data, 7))
				row[9] = resolveString(resolve(announcement.Data, 8))
				row[10] = resolveString(resolve(announcement.Data, 9))
				row[11] = resolveString(resolve(announcement.Data, 10))
				row[12] = resolvePhone(resolve(announcement.Data, 11))
				row[13] = resolveString(resolve(announcement.Data, 12))
				row[14] = resolveString(resolve(announcement.Data, 13))
				row[15] = resolveTime(resolve(announcement.Data, 14), resolve(announcement.Data, 15))
				row[16] = resolveString(resolve(announcement.Data, 16))
				row[17] = resolveString(resolve(announcement.Data, 17))
				row[18] = resolveString(resolve(announcement.Data, 18))
				row[19] = resolveFloat(resolve(announcement.Data, 19))
				row[20] = resolveFloat(resolve(announcement.Data, 20))
				row[21] = resolveFloat(resolve(announcement.Data, 21))
			case 1:
				row[4] = resolveString(resolve(announcement.Data, 0))
				row[5] = resolveString(resolve(announcement.Data, 1))
				row[6] = resolveString(resolve(announcement.Data, 2))
				row[7] = resolveString(resolve(announcement.Data, 3))
				row[8] = resolveString(resolve(announcement.Data, 4))
				row[9] = resolveString(resolve(announcement.Data, 5))
				row[10] = resolveString(resolve(announcement.Data, 6))
				row[11] = resolveString(resolve(announcement.Data, 7))
				row[12] = resolvePhone(resolve(announcement.Data, 8))
				row[13] = resolveString(resolve(announcement.Data, 9))
				row[14] = resolveString(resolve(announcement.Data, 10))
				row[15] = resolveTime(resolve(announcement.Data, 11), resolve(announcement.Data, 12))
				row[20] = resolveFloat(resolve(announcement.Data, 13))
				row[21] = resolveFloat(resolve(announcement.Data, 14))

			case 2:
				row[4] = resolveString(resolve(announcement.Data, 0))
				row[5] = resolveString(resolve(announcement.Data, 1))
				row[6] = resolveString(resolve(announcement.Data, 2))
				row[7] = resolveString(resolve(announcement.Data, 3))
				row[8] = resolveString(resolve(announcement.Data, 4))
				row[9] = resolveString(resolve(announcement.Data, 5))
				row[10] = resolveString(resolve(announcement.Data, 6))
				row[11] = resolveString(resolve(announcement.Data, 7))
				row[12] = resolvePhone(resolve(announcement.Data, 8))
				row[13] = resolveString(resolve(announcement.Data, 9))
				row[14] = resolveString(resolve(announcement.Data, 10))
				row[15] = resolveTime(resolve(announcement.Data, 11))

			case 3:
				row[4] = resolveString(resolve(announcement.Data, 0))
				row[5] = resolveString(resolve(announcement.Data, 1))
				row[6] = resolveString(resolve(announcement.Data, 2))
				row[7] = resolveString(resolve(announcement.Data, 3))
				row[8] = resolveString(resolve(announcement.Data, 4))
				row[10] = resolveString(resolve(announcement.Data, 5))
				row[11] = resolveString(resolve(announcement.Data, 6))
				row[12] = resolvePhone(resolve(announcement.Data, 7))
				row[13] = resolveString(resolve(announcement.Data, 8))
				row[14] = resolveString(resolve(announcement.Data, 9))
				row[15] = resolveTime(resolve(announcement.Data, 10))

			default:
				logrus.WithField("announcement", announcement).Errorf("announcement not supported")

				continue
			}

			rows = append(rows, row)
			if len(rows) > 100_000 {
				if _, err := conn.CopyFrom(ctx, pgx.Identifier{schema, table}, columns, pgx.CopyFromRows(rows)); err != nil {
					logrus.WithError(err).Fatal("failed to copy data")
				}

				rows = make([][]any, 0)
			}
		}

		if len(rows) > 0 {
			if _, err := conn.CopyFrom(ctx, pgx.Identifier{schema, table}, columns, pgx.CopyFromRows(rows)); err != nil {
				logrus.WithError(err).Fatal("failed to copy data")
			}
		}
	}()

	var waitGroup errgroup.Group

	waitGroup.SetLimit(runtime.GOMAXPROCS(runtime.NumCPU()))

	pb := progressbar.Default(int64(len(entries)))

	for _, entry := range entries {
		ext := filepath.Ext(entry.Name())
		if ext != ".gz" {
			_ = pb.Add(1)

			continue
		}

		filename := filepath.Join(folder, entry.Name())

		waitGroup.Go(func() error {
			//goland:noinspection GoUnhandledErrorResult
			defer pb.Add(1)

			file, err := os.Open(filename)
			if err != nil {
				return fmt.Errorf("failed to open file: %w", err)
			}

			//goland:noinspection GoUnhandledErrorResult
			defer file.Close()

			gZipReader, err := gzip.NewReader(file)
			if err != nil {
				return fmt.Errorf("failed to open gzip reader: %w", err)
			}

			//goland:noinspection GoUnhandledErrorResult
			defer gZipReader.Close()

			tarReader := tar.NewReader(gZipReader)

			for {
				header, err := tarReader.Next()

				if err != nil {
					if errors.Is(err, io.EOF) {
						break
					}

					return fmt.Errorf("failed to read tar header: %w", err)
				}

				if header.FileInfo().IsDir() {
					continue
				}

				var (
					buffer       bytes.Buffer
					bufferWriter = bufio.NewWriter(&buffer)
				)

				if _, err = io.Copy(bufferWriter, tarReader); err != nil {
					return fmt.Errorf("failed to copy tar contents: %w", err)
				}

				var announcements []*Announcement
				if err = json.Unmarshal(buffer.Bytes(), &announcements); err != nil {
					return fmt.Errorf("failed to unmarshal data: %w", err)
				}

				for _, announcement := range announcements {
					announcementsChannel <- announcement
				}
			}

			return nil
		})
	}

	if err = waitGroup.Wait(); err != nil {
		return fmt.Errorf("failed to process: %w", err)
	}

	//goland:noinspection GoUnhandledErrorResult
	pb.Finish()

	close(announcementsChannel)
	<-announcementsDone

	return nil
}

func resolve(where []string, idx int) string {
	if idx > len(where)-1 {
		return ""
	}

	return where[idx]
}

func resolveURL(v string) *string {
	urlObj, err := url.Parse(v)
	if err != nil {
		logrus.WithError(err).WithField("value", v).Warn("cannot parse URL")

		return nil
	}

	return resolveString(urlObj.String())
}

func resolveString(v string) *string {
	v = strings.TrimSpace(v)
	if v == "" {
		return nil
	}

	return &v
}

func resolvePhone(v string) *int {
	phonenumber, err := libphonenumber.Parse(v, "RU")
	if err != nil {
		logrus.WithError(err).WithField("value", v).Warn("cannot parse phone")

		return nil
	}

	phoneStr := libphonenumber.Format(phonenumber, libphonenumber.E164)
	phoneStr = strings.TrimPrefix(phoneStr, "+")
	phoneNum, err := strconv.Atoi(phoneStr)
	if err != nil {
		logrus.WithError(err).WithField("value", phoneStr).Warn("cannot cast number as phone")

		return nil
	}

	return &phoneNum
}

func resolveTime(parts ...string) *time.Time {
	var (
		str    = parts[0]
		layout string
	)

	if len(parts) > 1 {
		str += " " + parts[1]
		layout = "02.01.2006 15:04:05"
	} else {
		layout = "02.01.2006"
	}

	res, err := time.Parse(layout, str)
	if err != nil {
		logrus.WithError(err).WithFields(logrus.Fields{
			"layout": layout,
			"value":  str,
		}).Warn("cannot parse time")

		return nil
	}

	return &res
}

func resolveFloat(v string) *float64 {
	if v == "" {
		return nil
	}

	res, err := strconv.ParseFloat(v, 64)
	if err != nil {
		logrus.WithError(err).WithField("value", v).Warn("cannot parse float")

		return nil
	}

	return &res
}

type Announcement struct {
	Scheme int      `json:"scheme"`
	Data   []string `json:"data"`
}
