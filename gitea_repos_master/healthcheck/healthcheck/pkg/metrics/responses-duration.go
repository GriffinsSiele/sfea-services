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

type ResponsesDuration struct {
	*prometheus.GaugeVec
}

func NewResponsesDuration() *ResponsesDuration {
	return &ResponsesDuration{
		prometheus.NewGaugeVec(
			prometheus.GaugeOpts{
				Name: "isphere_responses_duration_seconds",
			},
			[]string{
				"period",
				"check_type",
				"code",
			},
		),
	}
}

func (d *ResponsesDuration) Register(ctx context.Context) error {
	duration, err := time.ParseDuration(os.Getenv("METRIC_RESPONSES_DURATION_TIMEOUT_DURATION"))
	if err != nil {
		return errors.Wrap(err, "failed to parse timeout duration")
	}

	prometheus.MustRegister(d)

	go func() {
		doUpdate := func() {
			if err := d.Update(ctx); err != nil {
				logrus.WithContext(ctx).WithError(err).Error("failed to update responses duration")
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

func (d *ResponsesDuration) Update(ctx context.Context) error {
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
       avg(process_time)
from ResponseNew
where created_at > (now() - interval ? minute)
group by checktype,
         res_code
having count(*) > 20
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

			for rows.Next() {
				var checkType string
				var code int
				var duration float64
				if err = rows.Scan(&checkType, &code, &duration); err != nil {
					slog.ErrorContext(ctx, "failed to scan", "error", err)
					return
				}

				d.GaugeVec.
					With(prometheus.Labels{
						"period":     (time.Duration(minutes) * time.Minute).String(),
						"check_type": checkType,
						"code":       strconv.Itoa(code),
					}).
					Set(duration)
			}
		}(minutes)
	}

	wg.Wait()

	return nil
}
