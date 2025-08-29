package regions

import (
	"fmt"
	"sync"
	"sync/atomic"

	"github.com/jackc/pgx/v5"
	"github.com/jackc/pgx/v5/pgxpool"
	"github.com/urfave/cli/v2"
)

type RegionsV2 struct{}

func NewRegionsV2() *RegionsV2 {
	return &RegionsV2{}
}

func (t *RegionsV2) Describe() *cli.Command {
	return &cli.Command{
		Category: "regions",
		Name:     "regions_v2",
		Action:   t.Execute,
		Usage:    "Внутренний справочник списка регионов (v2)",
		Hidden:   true,
		Flags: cli.FlagsByName{
			&cli.StringFlag{Name: "database", Required: true, EnvVars: []string{"ISPHERE_GRABBER_DATABASE"}},
			&cli.StringFlag{Name: "schema", Value: "regions"},
			&cli.StringFlag{Name: "table1", Value: "regions_v2_districts"},
			&cli.StringFlag{Name: "table2", Value: "regions_v2"},
		},
	}
}

// @see https://www.consultant.ru/document/cons_doc_LAW_215444/635c8363f3a56fc3a8c3efb0c244531b8ce38828/
func (t *RegionsV2) Execute(c *cli.Context) error {
	var (
		ctx = c.Context

		databaseStr = c.String("database")
		schema      = c.String("schema")
		table1      = c.String("table1")
		table2      = c.String("table2")
	)

	pool, err := pgxpool.New(ctx, databaseStr)
	if err != nil {
		return fmt.Errorf("failed to create pgxpool: %w", err)
	}

	defer pool.Close()

	conn, err := pool.Acquire(ctx)
	if err != nil {
		return fmt.Errorf("failed to acquire database connection: %w", err)
	}

	defer conn.Release()

	tx, err := conn.Begin(ctx)
	if err != nil {
		return fmt.Errorf("failed to open database transaction: %w", err)
	}

	//goland:noinspection GoUnhandledErrorResult
	defer tx.Rollback(ctx) //nolint:errcheck

	if _, err = tx.Exec(ctx, fmt.Sprintf("truncate table %s.%s cascade", schema, table1)); err != nil {
		return fmt.Errorf("failed to execute truncate table1: %w", err)
	}

	if _, err = tx.Exec(ctx, fmt.Sprintf("truncate table %s.%s cascade", schema, table2)); err != nil {
		return fmt.Errorf("failed to execute truncate table2: %w", err)
	}

	var (
		rows1          [][]any
		rows2          [][]any
		id1Seq         atomic.Uint64
		id2Seq         atomic.Uint64
		duplicateCheck sync.Map
	)

	for district, regions := range items {
		var id1 uint64

		if district != "" {
			id1 = id1Seq.Add(1)
			rows1 = append(rows1, []any{
				id1,
				district,
			})
		}

		for region, code := range regions {
			id2 := id2Seq.Add(1)

			if _, ok := duplicateCheck.Load(code); ok {
				return fmt.Errorf("duplicate code: %s", code)
			}

			duplicateCheck.Store(code, region)

			var distr *uint64
			if id1 > 0 {
				distr = &id1
			}

			rows2 = append(rows2, []any{
				id2,
				distr,
				code,
				region,
			})
		}
	}

	columns1 := []string{"id", "name"}

	if _, err = tx.CopyFrom(ctx, pgx.Identifier{schema, table1}, columns1, pgx.CopyFromRows(rows1)); err != nil {
		return fmt.Errorf("failed to copy from rows1: %w", err)
	}

	columns2 := []string{"id", "district_id", "code", "name"}

	if _, err = tx.CopyFrom(ctx, pgx.Identifier{schema, table2}, columns2, pgx.CopyFromRows(rows2)); err != nil {
		return fmt.Errorf("failed to copy from rows2: %w", err)
	}

	if err = tx.Commit(ctx); err != nil {
		return fmt.Errorf("failed to commit: %w", err)
	}

	return nil
}

var items = map[string]map[string]string{
	"Центральный федеральный округ": {
		"Москва":               "77",
		"Белгородская область": "31",
		"Брянская область":     "32",
		"Владимирская область": "33",
		"Воронежская область":  "36",
		"Ивановская область":   "37",
		"Калужская область":    "40",
		"Костромская область":  "44",
		"Курская область":      "46",
		"Липецкая область":     "48",
		"Московская область":   "50",
		"Орловская область":    "57",
		"Рязанская область":    "62",
		"Смоленская область":   "67",
		"Тамбовская область":   "68",
		"Тверская область":     "69",
		"Тульская область":     "71",
		"Ярославская область":  "76",
	},
	"Северо-Западный федеральный округ": {
		"Санкт-Петербург":         "78",
		"Архангельская область":   "29",
		"Вологодская область":     "35",
		"Калининградская область": "39",
		"Республика Карелия":      "10",
		"Республика Коми":         "11",
		"Ленинградская область":   "47",
		"Мурманская область":      "51",
		"Ненецкий АО":             "83",
		"Новгородская область":    "53",
		"Псковская область":       "60",
	},
	"Южный федеральный округ": {
		"Республика Адыгея":      "01",
		"Астраханская область":   "30",
		"Волгоградская область":  "34",
		"Республика Дагестан":    "05",
		"Республика Ингушетия":   "06",
		"Кабардино-Балкария":     "07",
		"Республика Калмыкия":    "08",
		"Карачаево-Черкессия":    "09",
		"Краснодарский край":     "23",
		"Ростовская область":     "61",
		"Северная Осетия-Алания": "15",
		"Ставропольский край":    "26",
		"Чеченская Республика":   "20",
	},
	"Приволжский федеральный округ": {
		"Республика Башкортостан": "02",
		"Кировская область":       "43",
		"Республика Марий-Эл":     "12",
		"Республика Мордовия":     "13",
		"Нижегородская область":   "52",
		"Оренбургская область":    "56",
		"Пензенская область":      "58",
		"Пермский край":           "59",
		"Самарская область":       "63",
		"Саратовская область":     "64",
		"Республика Татарстан":    "16",
		"Удмуртская Республика":   "18",
		"Ульяновская область":     "73",
		"Чувашская Республика":    "21",
	},
	"Уральский федеральный округ": {
		"Курганская область":   "45",
		"Свердловская область": "66",
		"Тюменская область":    "72",
		"Ханты-Мансийский АО":  "86",
		"Челябинская область":  "74",
		"Ямало-Ненецкий АО":    "89",
	},
	"Сибирский федеральный округ": {
		"Республика Алтай":      "04",
		"Алтайский край":        "22",
		"Республика Бурятия":    "03",
		"Иркутская область":     "38",
		"Кемеровская область":   "42",
		"Красноярский край":     "24",
		"Новосибирская область": "54",
		"Омская область":        "55",
		"Республика Тыва":       "17",
		"Томская область":       "70",
		"Республика Хакасия":    "19",
	},
	"Дальневосточный федеральный округ": {
		"Амурская область":         "28",
		"Еврейская АО":             "79",
		"Забайкальский край":       "75",
		"Камчатский край":          "41",
		"Магаданская область":      "49",
		"Приморский край":          "25",
		"Республика Саха (Якутия)": "14",
		"Сахалинская область":      "65",
		"Хабаровский край":         "27",
		"Чукотский АО":             "87",
	},
	"Крымский федеральный округ": {
		"Республика Крым": "91",
		"Севастополь":     "92",
	},
	"": {
		"Донецкая Народная Республика":                        "80",
		"Запорожская область":                                 "85",
		"Луганская Народная Республика":                       "81",
		"Херсонская область":                                  "84",
		"Иные территории, включая город и космодром Байконур": "99",
	},
}
