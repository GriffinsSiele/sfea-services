package command

import (
	"bufio"
	"encoding/xml"
	"errors"
	"fmt"
	"math"
	"net/url"
	"os"
	"os/exec"
	"path"
	"path/filepath"
	"regexp"
	"strconv"
	"strings"
	"time"

	"git.i-sphere.ru/isphere-go-modules/grabber/internal/util"
	"github.com/antchfx/htmlquery"
	"github.com/jackc/pgx/v5"
	"github.com/sirupsen/logrus"
	"github.com/urfave/cli/v2"
	"golang.org/x/sync/errgroup"
)

type MinJust struct{}

func NewMinJust() *MinJust {
	return &MinJust{}
}

func (t *MinJust) Describe() *cli.Command {
	return &cli.Command{
		Category: "minjust",
		Name:     "minjust:foreigns",
		Action:   t.Execute,
		Usage:    "Реестр иностранных агентов",
		Flags: cli.FlagsByName{
			&cli.StringFlag{Name: "database", Required: true, EnvVars: []string{"ISPHERE_GRABBER_DATABASE"}},
			&cli.StringFlag{Name: "schema", Value: "minjust"},
			&cli.StringFlag{Name: "persons-table", Value: "foreign_persons"},
			&cli.StringFlag{Name: "organizations-table", Value: "foreign_organizations"},
			&cli.BoolFlag{Name: "no-cache", Value: false},
			&cli.StringFlag{Name: "url", Value: "https://minjust.gov.ru/ru/activity/directions/998/"},
		},
	}
}

func (t *MinJust) Execute(c *cli.Context) error {
	filename, err := util.PgCache(c.Context, c.String("url"), c.Bool("no-cache"))
	if err != nil {
		return fmt.Errorf("failed to fetch source: %w", err)
	}

	document, err := htmlquery.LoadDoc(filename)
	if err != nil {
		return fmt.Errorf("failed to load document: %w", err)
	}

	linkNode, err := htmlquery.Query(document, "//a[contains(text(), 'Реестр иностранных агентов')]")
	if err != nil {
		return fmt.Errorf("failed to find link")
	}

	hrefAttr := htmlquery.SelectAttr(linkNode, "href")
	if hrefAttr == "" {
		return errors.New("link is empty")
	}

	if path.Ext(hrefAttr) != ".pdf" {
		return fmt.Errorf("link is not supports, required pdf extension: %w", hrefAttr)
	}

	hrefURI, err := url.Parse(hrefAttr)
	if err != nil {
		return fmt.Errorf("failed to parse href attr: %w", err)
	}

	if hrefURI.Scheme == "" {
		baseURI, _ := url.Parse(c.String("url"))
		hrefURI.Scheme = baseURI.Scheme
		hrefURI.Host = baseURI.Host
		hrefAttr = hrefURI.String()
	}

	pdfFilename, err := util.PgCache(c.Context, hrefAttr, c.Bool("no-cache"))
	if err != nil {
		return fmt.Errorf("failed to fetch pdf: %w", err)
	}

	tmpFile, err := os.OpenFile(filepath.Join(os.TempDir(), util.Hash(pdfFilename)+".xml"), os.O_CREATE|os.O_RDWR, 0o0644)
	if err != nil {
		return fmt.Errorf("failed to create temp: %w", err)
	}

	defer func() {
		if err := tmpFile.Close(); err != nil {
			logrus.WithError(err).Error("failed to close temp: %w", err)
		}

		if err := os.Remove(tmpFile.Name()); err != nil {
			logrus.WithError(err).Error("failed to remove temp: %w", err)
		}
	}()

	cmd := exec.Command("sh", "-c", fmt.Sprintf(`
set -ex
/usr/bin/env pdftohtml -s -p -nodrm -xml %s %s
`, pdfFilename, tmpFile.Name()))
	cmd.Stdout = os.Stdout
	cmd.Stderr = os.Stderr

	if err = cmd.Start(); err != nil {
		return fmt.Errorf("failed to start pdftotext cmd: %w", err)
	}

	if err = cmd.Wait(); err != nil {
		return fmt.Errorf("failed to wait pdftotext cmd: %w", err)
	}

	if _, err = tmpFile.Seek(0, 0); err != nil {
		return fmt.Errorf("failed to seek temp file: %w", err)
	}

	var (
		scanner = bufio.NewScanner(tmpFile)
		res     = map[int]*Item{}
		cursor  int
	)

	var (
		minNumLeft          int
		minNameLeft         int
		minBirthdayLeft     int
		minOGRNLeft         int
		minINNLeft          int
		minRegNumLeft       int
		minSNILSLeft        int
		minAddressLeft      int
		minInfoResourceLeft int
		minFullNameLeft     int
		minReasonLeft       int
		minCreatedAtLeft    int
		minDeletedAtLeft    int
	)

	var alreadyCreatedAtPresent bool
	for scanner.Scan() {
		var row Row
		if err := xml.Unmarshal(scanner.Bytes(), &row); err != nil {
			continue
		}

		if row.Definition != "" {
			switch row.Definition {
			case "№ п/п":
				minNumLeft = row.Left
			case "Полное наименование/ФИО":
				minNameLeft = row.Left
			case "Дата рождения":
				minBirthdayLeft = row.Left
			case "ОГРН":
				minOGRNLeft = row.Left
			case "ИНН":
				minINNLeft = row.Left
			case "Регистрационный ":
				minRegNumLeft = row.Left
			case "СНИЛС":
				minSNILSLeft = row.Left
			case "Адрес (место нахождения)":
				minAddressLeft = row.Left
			case "Информационный ресурс":
				minInfoResourceLeft = row.Left
			case "Полное наименование/ФИО ":
				minFullNameLeft = row.Left
			case "Основания включения":
				minReasonLeft = row.Left
			case "Дата принятия ":
				if !alreadyCreatedAtPresent {
					minCreatedAtLeft = row.Left
					alreadyCreatedAtPresent = true
				} else {
					minDeletedAtLeft = row.Left
					alreadyCreatedAtPresent = false
				}
			}

			continue
		}

		if row.IsIdentifier() {
			cursor = row.GetIdentifier()
			res[cursor] = &Item{ID: cursor}

			continue
		}

		if cursor == 0 {
			continue
		}

		if nearest(row.Left, minDeletedAtLeft, minCreatedAtLeft) {
			res[cursor].DeletedAt = row.GetDate()
		} else if nearest(row.Left, minCreatedAtLeft, minReasonLeft) {
			res[cursor].CreatedAt = row.GetDate()
		} else if nearest(row.Left, minReasonLeft, minFullNameLeft) {
			res[cursor].Reason = strings.TrimSpace(res[cursor].Reason + " " + row.GetValue())
		} else if nearest(row.Left, minFullNameLeft, minInfoResourceLeft) {
			res[cursor].FullName = strings.TrimSpace(res[cursor].FullName + " " + row.GetValue())
		} else if nearest(row.Left, minInfoResourceLeft, minAddressLeft) {
			res[cursor].InfoResource = strings.TrimSpace(res[cursor].InfoResource + " " + row.GetValue())
		} else if nearest(row.Left, minAddressLeft, minSNILSLeft) {
			res[cursor].Address = strings.TrimSpace(res[cursor].Address + " " + row.GetValue())
		} else if nearest(row.Left, minSNILSLeft, minRegNumLeft) {
			res[cursor].SNILS = strings.TrimSpace(res[cursor].SNILS + " " + row.GetValue())
		} else if nearest(row.Left, minRegNumLeft, minINNLeft) {
			res[cursor].RegNum = strings.TrimSpace(res[cursor].RegNum + " " + row.GetValue())
		} else if nearest(row.Left, minINNLeft, minOGRNLeft) {
			res[cursor].INN = strings.TrimSpace(res[cursor].INN + " " + row.GetValue())
		} else if nearest(row.Left, minOGRNLeft, minBirthdayLeft) {
			res[cursor].OGRN = strings.TrimSpace(res[cursor].OGRN + " " + row.GetValue())
		} else if nearest(row.Left, minBirthdayLeft, minNameLeft) {
			res[cursor].Birthday = row.GetDate()
		} else if nearest(row.Left, minNameLeft, minNumLeft) {
			res[cursor].Name = strings.TrimSpace(res[cursor].Name + " " + row.GetValue())
		}
	}

	var (
		persons       []*Item
		organizations []*Item
		trash         []*Item
	)

	for _, item := range res {
		if (item.Name != "" && item.Birthday != nil && item.Age() > 0 && item.Age() < 100) || (item.INN != "" && len(item.INN) == 12) {
			persons = append(persons, item)
		} else if item.INN != "" && len(item.INN) == 10 {
			organizations = append(organizations, item)
		} else {
			trash = append(trash, item)
		}
	}

	logrus.WithField("len", len(trash)).Warn("trash records")

	pool, err := util.PgConnect(c.Context, c.String("database"))
	if err != nil {
		return fmt.Errorf("failed to create db pool: %w", err)
	}

	defer pool.Close()

	var wg errgroup.Group

	// persons
	wg.Go(func() error {
		conn, err := pool.Acquire(c.Context)
		if err != nil {
			return fmt.Errorf("failed to acquire pg: %w", err)
		}

		defer conn.Release()

		if _, err := conn.Exec(c.Context, fmt.Sprintf("truncate table %s.%s",
			c.String("schema"), c.String("persons-table"))); err != nil {
			return fmt.Errorf("failed to truncate persons table: %w", err)
		}

		var (
			rows     = make([][]any, len(persons))
			spacesRe = regexp.MustCompile(`\s+`)
		)

		for i, person := range persons {
			person.Name = spacesRe.ReplaceAllString(person.Name, " ")

			var (
				components    = strings.SplitN(person.Name, " ", 3)
				surname, name = components[0], components[1]
				patronymic    string
			)

			if len(components) > 2 {
				patronymic = components[2]
			}

			rows[i] = []any{
				i + 1,
				util.PgNullable(strings.ToUpper(surname)),
				util.PgNullable(strings.ToUpper(name)),
				util.PgNullable(strings.ToUpper(patronymic)),
				person.Birthday,
				util.PgNullable(person.INN),
				util.PgNullable(person.SNILS),
				util.PgNullable(person.Address),
				util.PgNullable(person.Reason),
				person.CreatedAt,
				person.DeletedAt,
			}
		}

		if _, err = conn.CopyFrom(c.Context, pgx.Identifier{c.String("schema"), c.String("persons-table")},
			[]string{"id", "surname", "name", "patronymic", "birthday", "inn", "snils", "address", "reason", "created_at", "deleted_at"},
			pgx.CopyFromRows(rows)); err != nil {
			return fmt.Errorf("failed to copy persons data: %w", err)
		}

		return nil
	})

	// organizations
	wg.Go(func() error {
		conn, err := pool.Acquire(c.Context)
		if err != nil {
			return fmt.Errorf("failed to acquire pg: %w", err)
		}

		defer conn.Release()

		if _, err := conn.Exec(c.Context, fmt.Sprintf("truncate table %s.%s",
			c.String("schema"), c.String("organizations-table"))); err != nil {
			return fmt.Errorf("failed to truncate organizations table: %w", err)
		}

		rows := make([][]any, len(organizations))

		for i, organization := range organizations {
			links := strings.Split(organization.InfoResource, ";")
			for i, link := range links {
				links[i] = strings.TrimSpace(link)
			}

			rows[i] = []any{
				i + 1,
				util.PgNullable(organization.Name),
				util.PgNullable(organization.INN),
				util.PgNullable(organization.RegNum),
				util.PgNullable(organization.Address),
				util.PgNullable(organization.Reason),
				organization.CreatedAt,
				organization.DeletedAt,
			}
		}

		if _, err = conn.CopyFrom(c.Context, pgx.Identifier{c.String("schema"), c.String("organizations-table")},
			[]string{"id", "name", "inn", "reg_num", "address", "reason", "created_at", "deleted_at"},
			pgx.CopyFromRows(rows)); err != nil {
			return fmt.Errorf("failed to copy organizations data: %w", err)
		}

		return nil
	})

	if err = wg.Wait(); err != nil {
		return fmt.Errorf("failed to push db data: %w", err)
	}

	return nil
}

type Item struct {
	ID           int        `json:"id,omitempty"`
	Name         string     `json:"name,omitempty"`
	Birthday     *time.Time `json:"birthday,omitempty"`
	OGRN         string     `json:"ogrn,omitempty"`
	INN          string     `json:"inn,omitempty"`
	RegNum       string     `json:"reg_num,omitempty"`
	SNILS        string     `json:"snils,omitempty"`
	Address      string     `json:"address,omitempty"`
	InfoResource string     `json:"info_resource,omitempty"`
	FullName     string     `json:"full_name,omitempty"`
	Reason       string     `json:"reason,omitempty"`
	CreatedAt    *time.Time `json:"created_at,omitempty"`
	DeletedAt    *time.Time `json:"deleted_at,omitempty"`
}

func (t *Item) Age() int {
	if t.Birthday == nil {
		return 0
	}

	diff := time.Since(*t.Birthday)

	return int(math.Floor(diff.Hours() / 24.0 / 365.0)) //  тут точность не нужна
}

type Row struct {
	XMLName    xml.Name `xml:"text"`
	Top        int      `xml:"top,attr"`
	Left       int      `xml:"left,attr"`
	Width      int      `xml:"width,attr"`
	Font       int      `xml:"font,attr"`
	Value      string   `xml:",chardata"`
	Definition string   `xml:",any"`
}

func (t *Row) GetValue() string {
	return strings.TrimSpace(t.Value)
}

func (t *Row) IsEmpty() bool {
	return t.GetValue() == ""
}

var numericRe = regexp.MustCompile(`^\d+$`)

func (t *Row) IsNumeric() bool {
	return numericRe.MatchString(t.GetValue())
}

func (t *Row) IsIdentifier() bool {
	if !t.IsNumeric() {
		return false
	}

	return len(t.GetValue()) < 6
}

func (t *Row) GetIdentifier() int {
	identifier, err := strconv.Atoi(t.GetValue())
	if err != nil {
		logrus.WithField("value", t.GetValue()).WithError(err).Error("failed to cast value as int")
	}

	return identifier
}

var dateRe = regexp.MustCompile(`^(\d{1,2})\.(\d{2})\.(\d{4})$`)

func (t *Row) IsDate() bool {
	return dateRe.MatchString(t.GetValue())
}

var snilsRe = regexp.MustCompile(`^\d{3}-\d{3}-\d{3}\s\d{2}$`)

func (t *Row) IsSnils() bool {
	return snilsRe.MatchString(t.GetValue())
}

var urlRe = regexp.MustCompile(`https?://.+`)

func (t *Row) IsURL() bool {
	return urlRe.MatchString(t.GetValue())
}

func (t *Row) IsString() bool {
	return !t.IsEmpty() && !t.IsNumeric() && !t.IsDate() && !t.IsURL() && !t.IsSnils()
}

func (t *Row) GetDate() *time.Time {
	if t.IsEmpty() {
		return nil
	}

	d, err := time.Parse("02.01.2006", t.GetValue())
	if err != nil {
		logrus.WithField("row", t).WithError(err).Error("failed to parse date")
	}

	return &d
}

func nearest(subject, expected, prev int) bool {
	center := (float64(expected) - float64(prev)) / 2.0

	return float64(expected)-center < float64(subject)
}
