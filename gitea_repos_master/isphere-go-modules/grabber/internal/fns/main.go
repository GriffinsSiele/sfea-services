package fns

import (
	"archive/zip"
	"context"
	"encoding/csv"
	"encoding/xml"
	"errors"
	"fmt"
	"io"
	"os"
	"path/filepath"
	"reflect"
	"regexp"
	"strconv"
	"strings"
	"time"

	"git.i-sphere.ru/isphere-go-modules/grabber/internal/util"
	"github.com/antchfx/htmlquery"
	"github.com/jackc/pgx/v5"
	"github.com/jackc/pgx/v5/pgxpool"
	"github.com/mitchellh/mapstructure"
	"github.com/schollz/progressbar/v3"
	"github.com/sirupsen/logrus"
	"golang.org/x/sync/errgroup"
)

const (
	DebtamURL               = "https://www.nalog.ru/opendata/7707329152-debtam/"
	DisqualifiedpersonsURL  = "https://www.nalog.ru/opendata/7707329152-disqualifiedpersons/"
	MasaddressURL           = "https://www.nalog.ru/opendata/7707329152-masaddress/"
	MassfoundersURL         = "https://www.nalog.ru/opendata/7707329152-massfounders/"
	MassleadersURL          = "https://www.nalog.ru/opendata/7707329152-massleaders/"
	PaytaxURL               = "https://www.nalog.ru/opendata/7707329152-paytax/"
	RevexpURL               = "https://www.nalog.ru/opendata/7707329152-revexp/"
	RegisterdisqualifiedURL = "https://www.nalog.ru/opendata/7707329152-registerdisqualified/"
	RsmpURL                 = "https://www.nalog.ru/opendata/7707329152-rsmp/"
	SnrURL                  = "https://www.nalog.ru/opendata/7707329152-snr/"
	SshrURL                 = "https://www.nalog.gov.ru/opendata/7707329152-sshr2019/" // Несмотря на то, что в ссылке 2019 год, это актуальный список
	TaxoffenceURL           = "https://www.nalog.ru/opendata/7707329152-taxoffence/"
)

func Invoke[T any](
	ctx context.Context,
	database, url string,
	noCache bool,
	apply func(*T, FlushFunc) error,
	done func(FlushFunc) error,
	schema string,
	tables ...string,
) error {
	filename, err := util.PgCache(ctx, url, noCache)
	if err != nil {
		return fmt.Errorf("failed to attach cache: %s: %w", url, err)
	}

	source, err := FindSource(filename)
	if err != nil {
		return fmt.Errorf("failed to find source: %w", err)
	}

	pool, err := util.PgConnect(ctx, database)
	if err != nil {
		return fmt.Errorf("failed to attach database: %w", err)
	}

	defer pool.Close()

	conn, err := pool.Acquire(ctx)
	if err != nil {
		return fmt.Errorf("failed to acquire pgx: %w", err)
	}

	defer conn.Release()

	if err = util.PgTruncate(ctx, conn, schema, tables...); err != nil {
		return fmt.Errorf("failed to cleanup database: %w", err)
	}

	if err = util.PgDisableTriggers(ctx, conn, schema, tables...); err != nil {
		return fmt.Errorf("failed to disable triggers: %w", err)
	}

	defer func() {
		if err := util.PgEnableTriggers(ctx, conn, schema, tables...); err != nil {
			logrus.WithError(err).Error("failed to enable triggers")
		}
	}()

	maxCoroutines := int(pool.Config().MaxConns * 10)
	//maxCoroutines = 1

	if err = Parse(ctx, source, noCache, maxCoroutines, func(obj *T) error {
		conn, err := pool.Acquire(ctx)
		if err != nil {
			return fmt.Errorf("failed to acquire database on the iterator: %w", err)
		}

		defer conn.Release()

		if err = apply(obj, func(rows [][]any, columns []string, schema, table string) error {
			return FlushFuncClosure(ctx, conn)(rows, columns, schema, table)
		}); err != nil {
			return fmt.Errorf("failed to apply obj: %w", err)
		}

		return nil
	}); err != nil {
		return fmt.Errorf("failed to parse source: %w", err)
	}

	if err = done(func(rows [][]any, columns []string, schema, table string) error {
		return FlushFuncClosure(ctx, conn)(rows, columns, schema, table)
	}); err != nil {
		return fmt.Errorf("failed to done obj: %w", err)
	}

	return nil
}

type FlushFunc func([][]any, []string, string, string) error

var NoFlush = func(FlushFunc) error { return nil }

func FlushFuncClosure(ctx context.Context, conn *pgxpool.Conn) FlushFunc {
	return func(rows [][]any, columns []string, schema, table string) error {
		if len(rows) == 0 {
			return nil
		}

		if _, err := conn.CopyFrom(ctx, pgx.Identifier{schema, table}, columns, pgx.CopyFromRows(rows)); err != nil {
			return fmt.Errorf("failed to copy data: %w", err)
		}

		return nil
	}
}

func FindSource(filename string) (string, error) {
	doc, err := htmlquery.LoadDoc(filename)
	if err != nil {
		return "", fmt.Errorf("failed to load document: %w", err)
	}

	dcSource, err := htmlquery.Query(doc, "//div[contains(@property, 'dc:source')]")
	if err != nil {
		return "", fmt.Errorf("failed to find dc:dcSource: %w", err)
	}

	dcSourceContent := strings.TrimSpace(htmlquery.SelectAttr(dcSource, "content"))
	if dcSourceContent == "" {
		return "", fmt.Errorf("dc:dcSource content is empty: %s", filename)
	}

	return dcSourceContent, nil
}

func Parse[T any](ctx context.Context, link string, noCache bool, processLimit int, resolve func(*T) error) error {
	filename, err := util.PgCache(ctx, link, noCache)
	if err != nil {
		return fmt.Errorf("failed to load cache file: %w", err)
	}

	switch filepath.Ext(filename) {
	case ".csv":
		fh, err := os.Open(filename)
		if err != nil {
			return fmt.Errorf("failed to open file: %w", err)
		}

		reader := csv.NewReader(fh)
		reader.LazyQuotes = true
		reader.Comma = ';'

		headers, err := reader.Read()
		if err != nil {
			return fmt.Errorf("failed to read headers")
		}

		fileInfo, err := os.Stat(filename)
		if err != nil {
			return fmt.Errorf("failed to get file stat: %w", err)
		}

		bar := progressbar.DefaultBytes(fileInfo.Size(), link)

		defer func() {
			if err := bar.Finish(); err != nil {
				logrus.WithError(err).Error("failed to finish progressbar")
			}
		}()

		for {
			values, err := reader.Read()

			if err != nil {
				if errors.Is(err, io.EOF) {
					break
				}

				return fmt.Errorf("failed to read csv: %w", err)
			}

			if err := bar.Set64(reader.InputOffset()); err != nil {
				logrus.WithError(err).Error("failed to set progressbar value")
			}

			row := map[string]string{}
			for i, header := range headers {
				row[header] = values[i]
			}

			var obj T

			decoderConfig := mapstructure.DecoderConfig{
				DecodeHook: func(fromType, toType reflect.Type, data any) (any, error) {
					switch {
					// util.Date
					case fromType == reflect.TypeOf("") && toType == reflect.TypeOf(util.Date{}):
						if parsed, err := time.Parse("02.01.2006", data.(string)); err != nil {
							return nil, err
						} else {
							return util.Date{Time: parsed}, nil
						}

					// *util.Date
					case fromType == reflect.TypeOf("") && toType == reflect.TypeOf(new(util.Date)):
						if parsed, err := time.Parse("02.01.2006", data.(string)); err != nil {
							return nil, err
						} else {
							return &util.Date{Time: parsed}, nil
						}

					// int
					case fromType == reflect.TypeOf("") && toType == reflect.TypeOf(0):
						return strconv.Atoi(data.(string))

					// util.Period
					case fromType == reflect.TypeOf("") && toType == reflect.TypeOf(util.Period{}):
						if period, err := castAsPeriod(data.(string)); err != nil {
							return nil, err
						} else {
							return *period, nil
						}

					// *util.Period
					case fromType == reflect.TypeOf("") && toType == reflect.TypeOf(new(util.Period)):
						if period, err := castAsPeriod(data.(string)); err != nil {
							return nil, err
						} else {
							return period, nil
						}
					}

					return data, nil
				},
				Result: &obj,
			}

			decoder, err := mapstructure.NewDecoder(&decoderConfig)
			if err != nil {
				return fmt.Errorf("failed to create decoder: %w", err)
			}

			if err := decoder.Decode(row); err != nil {
				return fmt.Errorf("failed to decode map structure: %w", err)
			}

			if err := resolve(&obj); err != nil {
				return fmt.Errorf("failed to resolve obj: %w", err)
			}
		}

		defer func() {
			if err := fh.Close(); err != nil {
				logrus.WithError(err).Fatal("failed to close csv file: %w", err)
			}
		}()

	case ".zip":
		zf, err := zip.OpenReader(filename)
		if err != nil {
			return fmt.Errorf("failed to open zip file: %w", err)
		}

		defer func() {
			if err := zf.Close(); err != nil {
				logrus.WithError(err).Fatal("failed to close zip file: %w", err)
			}
		}()

		var wg errgroup.Group

		wg.SetLimit(processLimit)

		bar := progressbar.Default(int64(len(zf.File)), link)

		defer func() {
			if err := bar.Finish(); err != nil {
				logrus.WithError(err).Error("failed to finish progressbar")
			}
		}()

		for _, file := range zf.File {
			if err := bar.Add64(1); err != nil {
				logrus.WithError(err).Error("failed to set progressbar value")
			}

			file := file

			wg.Go(func() error {
				if file.FileInfo().IsDir() {
					return nil
				}

				fh, err := file.Open()
				if err != nil {
					return fmt.Errorf("failed to open archive file: %w", err)
				}

				defer func() {
					if err := fh.Close(); err != nil {
						logrus.WithError(err).Fatal("failed to close archive file: %w", err)
					}
				}()

				switch filepath.Ext(file.Name) {
				case ".xml":
					var obj T
					if err := xml.NewDecoder(fh).Decode(&obj); err != nil {
						return fmt.Errorf("failed to unmarshal proto: %w", err)
					}

					if err := resolve(&obj); err != nil {
						return fmt.Errorf("failed to resolve object: %w", err)
					}
				default:
					return fmt.Errorf("unsupported archive file extension: %s", file.Name)
				}

				return nil
			})
		}

		if err := wg.Wait(); err != nil {
			return fmt.Errorf("failed to process archive: %w", err)
		}

	default:
		return fmt.Errorf("unsupported filename extension: %s", filename)
	}

	return nil
}

var periodRe = regexp.MustCompile(`^(\d+) г (\d+) м (\d+) д$`)

func castAsPeriod(data string) (*util.Period, error) {
	matches := periodRe.FindStringSubmatch(data)
	if len(matches) != 4 {
		return nil, fmt.Errorf("failed to match period: %s", data)
	}

	years, err := strconv.Atoi(matches[1])
	if err != nil {
		return nil, fmt.Errorf("failed to parse years: %w", err)
	}

	months, err := strconv.Atoi(matches[2])
	if err != nil {
		return nil, fmt.Errorf("failed to parse months: %w", err)
	}

	days, err := strconv.Atoi(matches[3])
	if err != nil {
		return nil, fmt.Errorf("failed to parse days: %w", err)
	}

	return &util.Period{
		Years:  years,
		Months: months,
		Days:   days,
	}, nil
}

type Date struct {
	time.Time
}

func (t *Date) UnmarshalXMLAttr(attr xml.Attr) error {
	parsed, err := time.Parse("02.01.2006", attr.Value)
	if err != nil {
		return fmt.Errorf("failed to parse date: %w", err)
	}

	*t = Date{parsed}

	return nil
}
