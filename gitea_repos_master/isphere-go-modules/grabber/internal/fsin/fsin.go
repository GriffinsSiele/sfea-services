package fsin

import (
	"context"
	"encoding/base64"
	"fmt"
	"io"
	"mime"
	"net/url"
	"os"
	"path"
	"regexp"
	"strconv"
	"strings"
	"sync/atomic"
	"time"

	"git.i-sphere.ru/isphere-go-modules/grabber/internal/util"
	"github.com/antchfx/htmlquery"
	"github.com/jackc/pgx/v5"
	"github.com/sirupsen/logrus"
	"github.com/urfave/cli/v2"
	"golang.org/x/text/encoding/charmap"
)

type Fsin struct {
	Parsed map[string]bool
}

func NewFsin() *Fsin {
	return &Fsin{
		Parsed: make(map[string]bool),
	}
}

func (t *Fsin) Describe() *cli.Command {
	return &cli.Command{
		Category: "fsin",
		Name:     "fsin",
		Action:   t.Execute,
		Usage:    "База данных розыска ФСИН",
		Flags: cli.FlagsByName{
			&cli.StringFlag{Name: "database", Required: true, EnvVars: []string{"ISPHERE_GRABBER_DATABASE"}},
			&cli.StringFlag{Name: "schema", Value: "fsin"},
			&cli.StringFlag{Name: "table", Value: "fsin"},
			&cli.BoolFlag{Name: "no-cache"},
			&cli.StringFlag{Name: "url", Value: "https://fsin.gov.ru/criminal/"},
			&cli.StringFlag{Name: "bucket", Value: "c2e09116-grabber"},
		},
	}
}

func (t *Fsin) Execute(c *cli.Context) error {
	var (
		ctx          = c.Context
		firstPageURL = c.String("url")
		noCache      = c.Bool("no-cache")
		database     = c.String("database")
		schema       = c.String("schema")
		table        = c.String("table")
	)

	ctx = context.WithValue(ctx, "user-agent", "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/51.0.2704.106 Safari/537.36 OPR/38.0.2220.41")

	firstPageFilename, err := util.PgCache(ctx, firstPageURL, noCache)
	if err != nil {
		return fmt.Errorf("failed to fetch first page: %w", err)
	}

	firstPageDocument, err := htmlquery.LoadDoc(firstPageFilename)
	if err != nil {
		return fmt.Errorf("failed to parse first page: %w", err)
	}

	lastPageURLNode, err := htmlquery.Query(firstPageDocument, "//a[@class='bp-end']")
	if err != nil {
		return fmt.Errorf("failed to extract last page URL: %w", err)
	}

	lastPageURL := "https://fsin.gov.ru" + htmlquery.SelectAttr(lastPageURLNode, "href")

	lastPageURLObj, err := url.Parse(lastPageURL)
	if err != nil {
		return fmt.Errorf("failed to parse last page url: %w", err)
	}

	lastPageURLValues, err := url.ParseQuery(lastPageURLObj.RawQuery)
	if err != nil {
		return fmt.Errorf("failed to parse last page url values: %w", err)
	}

	lastPageN, err := strconv.Atoi(lastPageURLValues.Get("PAGEN_1"))
	if err != nil {
		return fmt.Errorf("failed to cast last page number as int: %w", err)
	}

	var (
		filenameToLinkMapping = map[string]string{}
		pageLinks             []string
	)

	for i := 2; i <= lastPageN; i++ {
		var (
			pageURLObj    = lastPageURLObj
			pageURLValues = url.Values{
				"PAGEN_1": []string{strconv.Itoa(i)},
			}
		)

		pageURLObj.RawQuery = pageURLValues.Encode()
		pageLinks = append(pageLinks, pageURLObj.String())
	}

	pageFilenames := []string{firstPageFilename}

	for _, pageLink := range pageLinks {
		var pageFilename string
		if pageFilename, err = util.TryPgCache(ctx, pageLink, noCache); err != nil {
			return fmt.Errorf("failed to get page: %s: %w", pageLinks, err)
		}

		pageFilenames = append(pageFilenames, pageFilename)
		filenameToLinkMapping[pageFilename] = pageLink
	}

	var (
		encoder = charmap.Windows1251.NewDecoder()
		re      = regexp.MustCompile(`(?mi)([а-яё].+?)\n?(\d{1,2}\.\d{2}\.\d{4})`)
		idSeq   atomic.Int64
		rows    [][]any
	)

	for _, pageFilename := range pageFilenames {
		file, err := os.Open(pageFilename)
		if err != nil {
			return fmt.Errorf("failed to open file: %w", err)
		}

		cp1251Content, err := io.ReadAll(file)
		if err != nil {
			return fmt.Errorf("failed to read file: %w", err)
		}

		//goland:noinspection GoUnhandledErrorResult
		file.Close()

		utf8Content, err := encoder.Bytes(cp1251Content)
		if err != nil {
			return fmt.Errorf("failed to convert bytes: %w", err)
		}

		utf8FileHandle, err := os.CreateTemp(os.TempDir(), "")
		if err != nil {
			return fmt.Errorf("failed to create tmp file: %w", err)
		}

		if _, err := utf8FileHandle.Write(utf8Content); err != nil {
			return fmt.Errorf("failed to write tmp file: %w", err)
		}

		//goland:noinspection GoUnhandledErrorResult
		utf8FileHandle.Close()

		pageDocument, err := htmlquery.LoadDoc(utf8FileHandle.Name())
		if err != nil {
			return fmt.Errorf("failed to read page document: %w", err)
		}

		slItemNodes, err := htmlquery.QueryAll(pageDocument, "//div[@class='sl-item']")
		if err != nil {
			return fmt.Errorf("failed to find sl-items: %w", err)
		}

		for _, slItemNode := range slItemNodes {
			var (
				remoteURL           string
				slItemImage         string
				slItemImageMimeType string
				slItemText          string
				territorialCode     string
				federalCode         string
			)

			{ // remote url
				linkURLNode, err := htmlquery.Query(slItemNode, "//div[contains(@class, 'sl-item-title')]/a")
				if err != nil {
					logrus.WithError(err).Error("failed to find link url node: %w", err)
				}

				if linkURLNode == nil {
					logrus.WithField("content", htmlquery.InnerText(slItemNode)).
						WithField("remote_url", filenameToLinkMapping[pageFilename]).
						Warn("sl-item-title/a not found on the page")
				} else {
					remoteURL = "https://fsin.gov.ru" + htmlquery.SelectAttr(linkURLNode, "href")
				}
			}

			{ // sl-item-image
				slItemImageNode, err := htmlquery.Query(slItemNode, "div[contains(@class, 'sl-item-image')]//img")
				if err != nil {
					return fmt.Errorf("failed to find sl-item-image: %w", err)
				}

				if slItemImageNode != nil {
					slItemImageSrc := "https://fsin.gov.ru" + htmlquery.SelectAttr(slItemImageNode, "src")
					slItemImageLocal, err := util.TryPgCache(ctx, slItemImageSrc, noCache)
					if err != nil {
						return fmt.Errorf("failed to get sl-item-image img: %w", err)
					}

					slItemImageFile, err := os.Open(slItemImageLocal)
					if err != nil {
						return fmt.Errorf("failed to open sl-item-image img: %w", err)
					}

					//goland:noinspection GoUnhandledErrorResult,GoDeferInLoop
					defer slItemImageFile.Close()

					ext := path.Ext(slItemImageLocal)
					slItemImageMimeType = mime.TypeByExtension(ext)

					skItemImageBytes, err := io.ReadAll(slItemImageFile)
					if err != nil {
						return fmt.Errorf("failed to read sl-item-image img: %w", err)
					}

					slItemImage = base64.StdEncoding.EncodeToString(skItemImageBytes)
				}
			}

			{ // sl-item-text
				slItemTextNode, err := htmlquery.Query(slItemNode, "//div[contains(@class, 'sl-item-text')]")
				if err != nil {
					return fmt.Errorf("failed to find sl-item-text: %w", err)
				}

				if slItemTextNode == nil {
					logrus.WithField("content", htmlquery.InnerText(slItemNode)).
						WithField("remote_url", filenameToLinkMapping[pageFilename]).
						Warn("sl-item-text not found on the page")
				} else {
					slItemText = util.Clean(htmlquery.InnerText(slItemTextNode))
				}
			}

			{ // territorial code
				territorialCodeNode, err := htmlquery.Query(slItemNode, "//p[contains(text(), 'Территориальные органы')]")
				if err != nil {
					return fmt.Errorf("failed to find territorial node: %w", err)
				}

				if territorialCodeNode != nil {
					territorialCodeParagraphs := strings.Split(htmlquery.InnerText(territorialCodeNode), "\n")
					territorialCode = util.Clean(territorialCodeParagraphs[len(territorialCodeParagraphs)-1])
				}
			}

			{ // federal code
				federalCodeNode, err := htmlquery.Query(slItemNode, "//p[contains(text(), 'Федеральные органы')]")
				if err != nil {
					return fmt.Errorf("failed to find federal node: %w", err)
				}

				if federalCodeNode != nil {
					federalCodeParagraphs := strings.Split(htmlquery.InnerText(federalCodeNode), "\n")
					federalCode = util.Clean(federalCodeParagraphs[len(federalCodeParagraphs)-1])
				}
			}

			{ // names
				fullname := strings.ToUpper(slItemText)
				fullname = strings.ReplaceAll(fullname, "РАЗЫСКИВАЕТСЯ", "")
				fullname = strings.TrimSpace(fullname)
				matches := re.FindStringSubmatch(fullname)

				if len(matches) == 0 {
					logrus.WithField("content", htmlquery.InnerText(slItemNode)).
						WithField("remote_url", filenameToLinkMapping[pageFilename]).
						Warn("full name not extracted on the page")
				} else {
					fullname = strings.Trim(matches[1], " ,-")
					fullname = regexp.MustCompile(`(\s+)`).ReplaceAllString(fullname, " ")
					variants := util.MakeVariants(fullname)

					for _, variant := range variants {
						var (
							fio        = strings.SplitN(variant, " ", 3)
							surname    = fio[0]
							name       = fio[1]
							patronymic *string
						)

						if len(fio) > 2 {
							patronymic = &fio[2]
						}

						if regexp.MustCompile(`^\d{1}\.`).MatchString(matches[2]) {
							matches[2] = "0" + matches[2]
						}

						birthday, err := time.Parse("02.01.2006", matches[2])
						if err != nil {
							logrus.WithField("content", htmlquery.InnerText(slItemNode)).
								WithField("remote_url", filenameToLinkMapping[pageFilename]).
								WithField("captured", matches[2]).
								WithError(err).
								Fatal("cannot parse birthday on the page")
						}

						id := idSeq.Add(1)

						rows = append(rows, []any{
							id,
							surname,
							name,
							patronymic,
							birthday,
							slItemText,
							federalCode,
							territorialCode,
							remoteURL,
							[]byte(slItemImage),
							slItemImageMimeType,
						})
					}
				}
			}
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

	if _, err = conn.CopyFrom(ctx, pgx.Identifier{schema, table},
		[]string{"id", "surname", "name", "patronymic", "birthday", "description", "federal_code", "territorial_code", "external_url", "image", "image_mime_type"},
		pgx.CopyFromRows(rows)); err != nil {
		return fmt.Errorf("failed to copy data: %w", err)
	}

	return nil
}
