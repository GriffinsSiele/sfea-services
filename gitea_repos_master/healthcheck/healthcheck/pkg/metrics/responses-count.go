package metrics

import (
	"context"
	"database/sql"
	"fmt"
	"log/slog"
	"os"
	"strconv"
	"sync"
	"time"

	_ "github.com/go-sql-driver/mysql"
	"github.com/pkg/errors"
	"github.com/prometheus/client_golang/prometheus"
	"github.com/sirupsen/logrus"
)

type ResponsesCount struct {
	responseCountGauge          *prometheus.GaugeVec
	responseHitRateByCountGaute *prometheus.GaugeVec
}

func NewResponsesCount() *ResponsesCount {
	return &ResponsesCount{
		responseCountGauge: prometheus.NewGaugeVec(
			prometheus.GaugeOpts{
				Name: "isphere_responses_count",
			},
			[]string{
				"period",
				"check_type",
				"code",
			},
		),

		responseHitRateByCountGaute: prometheus.NewGaugeVec(
			prometheus.GaugeOpts{
				Name: "isphere_responses_hitrate_by_count",
			},
			[]string{
				"period",
				"check_type",
			},
		),
	}
}

func (c *ResponsesCount) Register(ctx context.Context) error {
	duration, err := time.ParseDuration(os.Getenv("METRIC_RESPONSES_COUNT_TIMEOUT_DURATION"))
	if err != nil {
		return errors.Wrap(err, "failed to parse timeout duration")
	}

	prometheus.MustRegister(c.responseCountGauge)
	prometheus.MustRegister(c.responseHitRateByCountGaute)

	go func() {
		doUpdate := func() {
			if err := c.Update(ctx); err != nil {
				logrus.WithContext(ctx).WithError(err).Error("failed to update responses count")
			}
		}

		doUpdate()

		for {
			select {
			case <-ctx.Done():
				return
			case <-time.After(duration):
				doUpdate()
			}
		}
	}()

	return nil
}

func (c *ResponsesCount) Update(ctx context.Context) error {
	dsn := fmt.Sprintf(
		"%s:%s@tcp(%s:%s)/%s",
		os.Getenv("MYSQL_USERNAME"),
		os.Getenv("MYSQL_PASSWORD"),
		os.Getenv("MYSQL_HOST"),
		os.Getenv("MYSQL_PORT"),
		os.Getenv("MYSQL_DATABASE"),
	)
	db, err := sql.Open("mysql", dsn)
	if err != nil {
		return errors.Wrap(err, "failed to open db")
	}
	//goland:noinspection GoUnhandledErrorResult
	defer db.Close()

	// language=MySQL
	query := `--
select checktype,
       res_code,
       count(*)
from ResponseNew
where created_at > (now() - interval ? minute)
group by checktype,
         res_code
`
	stmt, err := db.PrepareContext(ctx, query)
	if err != nil {
		return errors.Wrap(err, "failed to prepare query")
	}
	//goland:noinspection GoUnhandledErrorResult
	defer stmt.Close()

	var wg sync.WaitGroup

	for _, minutes := range []int{10, 60} {
		wg.Add(1)

		go func(minutes int) {
			defer wg.Done()

			rows, err := stmt.QueryContext(ctx, minutes)
			if err != nil {
				slog.ErrorContext(ctx, "failed to query", "error", err)
				return
			}
			//goland:noinspection GoUnhandledErrorResult
			defer rows.Close()

			statistics := make(map[string]map[int]float64)

			for rows.Next() {
				var checkType string
				var code int
				var count float64
				if err = rows.Scan(&checkType, &code, &count); err != nil {
					slog.ErrorContext(ctx, "failed to scan", "error", err)
					return
				}

				if _, ok := statistics[checkType]; !ok {
					statistics[checkType] = make(map[int]float64)
				}

				if _, ok := statistics[checkType][code]; !ok {
					statistics[checkType][code] = 0
				}

				statistics[checkType][code] += count

				c.responseCountGauge.
					With(prometheus.Labels{
						"period":     (time.Duration(minutes) * time.Minute).String(),
						"check_type": checkType,
						"code":       strconv.Itoa(code),
					}).
					Set(count)
			}

			for checkType, codes := range statistics {
				var success float64
				var failed float64
				for code, count := range codes {
					if code < 500 {
						success += count
					} else {
						failed += count
					}
				}
				relation := 1 - failed/success

				c.responseHitRateByCountGaute.
					With(prometheus.Labels{
						"period":     (time.Duration(minutes) * time.Minute).String(),
						"check_type": checkType,
					}).
					Set(relation)
			}
		}(minutes)
	}

	wg.Wait()

	return nil
}
