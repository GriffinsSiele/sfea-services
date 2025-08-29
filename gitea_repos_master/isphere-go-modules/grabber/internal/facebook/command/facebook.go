package command

import (
	"bufio"
	"fmt"
	"os"
	"path/filepath"
	"regexp"
	"strings"
	"unicode/utf8"

	"github.com/dimchansky/utfbom"
	"github.com/jackc/pgx/v5"
	"github.com/jackc/pgx/v5/pgxpool"
	"github.com/sirupsen/logrus"
	"github.com/urfave/cli/v2"
	"golang.org/x/text/encoding/unicode"
)

type Facebook struct{}

func NewFacebook() *Facebook {
	return &Facebook{}
}

func (t *Facebook) Describe() *cli.Command {
	return &cli.Command{
		Category: "facebook",
		Name:     "facebook",
		Action:   t.Execute,
		Usage:    "База данных утечки Facebook",
		Flags: cli.FlagsByName{
			&cli.StringFlag{Name: "database", Required: true, EnvVars: []string{"ISPHERE_GRABBER_DATABASE"}},
			&cli.StringFlag{Name: "schema", Value: "facebook"},
			&cli.StringFlag{Name: "table", Value: "facebook"},
			&cli.BoolFlag{Name: "drop", Value: false},
			&cli.StringFlag{Name: "folder", Value: "./var/example"},
			&cli.BoolFlag{Name: "indexing", Value: false},
		},
	}
}

func (t *Facebook) Execute(c *cli.Context) error {
	var (
		ctx = c.Context

		databaseStr = c.String("database")
		schema      = c.String("schema")
		table       = c.String("table")
		folder      = c.String("folder")
		drop        = c.Bool("drop")
	)

	pool, err := pgxpool.New(ctx, databaseStr)
	if err != nil {
		return fmt.Errorf("failed to create pgxpool: %w", err)
	}

	defer pool.Close()

	conn, err := pool.Acquire(ctx)
	if err != nil {
		return fmt.Errorf("failed to acquire db: %w", err)
	}

	defer conn.Release()

	dirs, err := os.ReadDir(folder)
	if err != nil {
		return fmt.Errorf("failed to read dir: %w", err)
	}

	var (
		re1     = regexp.MustCompile(`^(\d{6,}):(\d{6,})`)
		re2     = regexp.MustCompile(`^"?(\d{6,})"?[,"]+"?\+(\d{6,})"?`)
		re3     = regexp.MustCompile(`^(\d{6,}),(\d{6,})`)
		reEmail = regexp.MustCompile("[a-zA-Z0-9.!#$%&'*+/=?^_`{|}~-]+@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*")
	)

	// Riyadh  Saudi Arabia
	for _, dir := range dirs {
		if !dir.IsDir() {
			continue
		}

		countryName := dir.Name()
		countryCode, ok := countries[countryName]
		if !ok {
			return fmt.Errorf("failed to get country code for country name: `%s`", countryName)
		}

		if _, err := os.Stat("./lock" + countryCode); err == nil {
			logrus.WithField("country_code", countryCode).Info("lock file exists, skipping update")

			continue
		}

		tableName := fmt.Sprintf("%s_%s", table, strings.ToLower(countryCode))

		if _, err := conn.Exec(ctx, fmt.Sprintf("truncate table %s.%s", schema, tableName)); err != nil {
			logrus.WithError(err).Warn("cannot truncate table")
		} else {
			logrus.WithFields(logrus.Fields{
				"schema":     schema,
				"table_name": tableName,
			}).Info("truncated table")
		}

		if drop {
			if _, err := conn.Exec(ctx, fmt.Sprintf("drop table %s.%s", schema, tableName)); err != nil {
				logrus.WithError(err).Warn("cannot drop table")
			} else {
				logrus.WithFields(logrus.Fields{
					"schema":     schema,
					"table_name": tableName,
				}).Info("dropped table")
			}
		}

		if _, err := conn.Exec(ctx, fmt.Sprintf(`
create table %s.%s (
    id varchar not null,
    phone varchar not null
)
`, schema, tableName)); err != nil {
			return fmt.Errorf("failed to create table: %w", err)
		} else {
			logrus.WithFields(logrus.Fields{
				"schema":     schema,
				"table_name": tableName,
			}).Info("created table")
		}

		files, err := os.ReadDir(filepath.Join(folder, dir.Name()))
		if err != nil {
			return fmt.Errorf("failed to read nested dir: %w", err)
		}

		decoder := unicode.UTF16(unicode.LittleEndian, unicode.IgnoreBOM).NewDecoder()

		for _, file := range files {
			if file.IsDir() {
				continue
			}

			filename := filepath.Join(folder, dir.Name(), file.Name())

			ext := filepath.Ext(filename)
			if ext != ".txt" {
				continue
			}

			fh, err := os.OpenFile(filename, os.O_RDONLY, 0o0644)
			if err != nil {
				return fmt.Errorf("failed to open file: %w", err)
			}

			//goland:noinspection GoUnhandledErrorResult,GoDeferInLoop
			defer fh.Close()

			logrus.WithFields(logrus.Fields{
				"country_code": countryCode,
				"filename":     filename,
				"table_name":   tableName,
			}).Info("working the file")

			scanner := bufio.NewScanner(utfbom.SkipOnly(fh))

			buf := make([]byte, 0, 64*1024)
			scanner.Buffer(buf, 1024*1024)

			var isUTF16 bool
			var buffer [][]any

			for scanner.Scan() {
				var line string

				lineBytes := scanner.Bytes()

				if isUTF16 || !utf8.Valid(lineBytes) {
					isUTF16 = true
					utf8Bytes, err := decoder.Bytes(lineBytes)
					if err != nil {
						return fmt.Errorf("failed to decode: %w", err)
					}
					line = string(utf8Bytes)
					continue
				} else {
					line = string(lineBytes)
				}

				line = reEmail.ReplaceAllString(line, "")

				var tel, id string

				if re1.MatchString(line) {
					match := re1.FindStringSubmatch(line)
					tel, id = match[1], match[2]
				} else if re2.MatchString(line) {
					match := re2.FindStringSubmatch(line)
					tel, id = strings.TrimLeft(match[2], "+"), match[1]
				} else if re3.MatchString(line) {
					match := re3.FindStringSubmatch(line)
					tel, id = strings.TrimLeft(match[1], "+"), match[2]
				} else {
					logrus.WithFields(logrus.Fields{
						"filename": filename,
						"line":     line,
					}).Warn("unsupported format, skipped")

					continue
				}

				buffer = append(buffer, []any{id, tel})

				//logrus.WithFields(logrus.Fields{
				//	"id":  id,
				//	"tel": tel,
				//}).Debug("add record")

				if len(buffer) > 100_000 {
					logrus.WithFields(logrus.Fields{
						"country_code": countryCode,
						"filename":     filename,
						"table_name":   tableName,
						"length":       len(buffer),
					}).Info("[!] copying buffer")

					cols := []string{"id", "phone"}
					if _, err := conn.CopyFrom(ctx, pgx.Identifier{schema, tableName}, cols, pgx.CopyFromRows(buffer)); err != nil {
						return fmt.Errorf("failed to copy from: %w", err)
					}

					buffer = make([][]any, 0)
				}
			}

			if err := scanner.Err(); err != nil {
				return fmt.Errorf("scanner error: %w", err)
			}

			if len(buffer) > 0 {
				logrus.WithFields(logrus.Fields{
					"country_code": countryCode,
					"filename":     filename,
					"table_name":   tableName,
					"length":       len(buffer),
				}).Info("[!] copying rest")

				cols := []string{"id", "phone"}
				if _, err := conn.CopyFrom(ctx, pgx.Identifier{schema, tableName}, cols, pgx.CopyFromRows(buffer)); err != nil {
					return fmt.Errorf("failed to copy from: %w", err)
				}
			}
		}

		lh, err := os.OpenFile("./lock"+countryCode, os.O_CREATE|os.O_RDWR, 0644)
		if err != nil {
			return fmt.Errorf("failed to open lock file: %w", err)
		}

		if _, err := lh.WriteString("1"); err != nil {
			return fmt.Errorf("failed to write to lock file: %w", err)
		}

		//goland:noinspection GoUnhandledErrorResult
		lh.Close()
	}

	return nil
}

type Item struct {
	ID    string
	Phone string
}

var countries = map[string]string{
	"Afghanistan":    "AF",
	"Albania":        "AL",
	"Algeria":        "DZ",
	"All Qatar":      "QA",
	"Angola":         "AO",
	"Argentina":      "AR",
	"Austria":        "AT",
	"Azerbaijan":     "AZ",
	"Bahrain":        "BH",
	"Bangladesh":     "BD",
	"Belgium":        "BE",
	"Bolivia":        "BO",
	"Botswana":       "BW",
	"Brazil":         "BR",
	"Brunei":         "BN",
	"Bulgaria":       "BG",
	"Burkina Faso":   "BF",
	"Burundi":        "BI",
	"Cambodia":       "KH",
	"Cameroon":       "CM",
	"Canada":         "CA",
	"Chile":          "CL",
	"China":          "CN",
	"Colombia":       "CO",
	"Costa Rica":     "CR",
	"Croatia":        "HR",
	"Cyprus":         "CY",
	"Czech Republic": "CZ",
	"Denmark":        "DK",
	"Dibouti":        "DJ",
	"Ecuador":        "EC",
	"Egypt":          "EG",
	"El Salvador":    "SV",
	"Estonia":        "EE",
	"Ethopia":        "ET",
	"Fiji":           "FJ",
	"Finland Text":   "FI",
	"France":         "FR",
	"Georgia":        "GE",
	"Germany":        "DE",
	"Ghana":          "GH",
	"Greece":         "GR",
	"Guatemala":      "GT",
	"Haiti":          "HT",
	"Honduras":       "HN",
	"Hong Kong":      "HK",
	"Hungary":        "HU",
	"Iceland":        "IS",
	"India":          "IN",
	"Indonesia":      "ID",
	"Iran":           "IR",
	"Iraq":           "IQ",
	"Ireland":        "IE",
	"Israel":         "IL",
	"Italy":          "IT",
	"Jamaica":        "JM",
	"Japan":          "JP",
	"Jordan":         "JO",
	"Kazakhstan":     "KZ",
	"Kuwait":         "KW",
	"Lebanon Text":   "LB",
	"Libya":          "LY",
	"Lithunia":       "LT",
	"Luxemburj":      "LU",
	"Macao":          "MO",
	"Malaysia":       "MY",
	"Maldives":       "MV",
	"Malta":          "MT",
	"Mauritus":       "MU",
	"Mexico":         "MX",
	"Moldova":        "MD",
	"Morocco":        "MA",
	"Namibia":        "NA",
	"Netherland":     "NL",
	"Nigeria":        "NG",
	"Norway":         "NO",
	"Oman":           "OM",
	"Palestine":      "PS",
	"Panama":         "PA",
	"Peru Complete":  "PE",
	"Philpine":       "PH",
	"Poland":         "PL",
	"Portugal":       "PT",
	"Puerto Rico":    "PR",
	"Russia":         "RU",
	"Saudi Arabia":   "SA",
	"Serbia":         "RS",
	"Singapore1":     "SG",
	"Slovenia":       "SI",
	"South Africa":   "ZA",
	"South Korea":    "KR",
	"Spain":          "ES",
	"Sudan":          "SD",
	"Sweden":         "SE",
	"Switzerland":    "CH",
	"Syria":          "SY",
	"Taiwan":         "TW",
	"Tunisia":        "TN",
	"Turkey":         "TR",
	"Turkmenistan":   "TM",
	"UAE":            "AE",
	"UK":             "GB",
	"Uruguay":        "UY",
	"USA":            "US",
	"Yemen":          "YE",
}
