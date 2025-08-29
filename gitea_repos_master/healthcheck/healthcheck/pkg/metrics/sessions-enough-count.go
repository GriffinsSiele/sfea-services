package metrics

import (
	"context"
	"database/sql"
	"fmt"
	"os"
	"time"

	_ "github.com/go-sql-driver/mysql"
	"github.com/pkg/errors"
	"github.com/prometheus/client_golang/prometheus"
	"github.com/sirupsen/logrus"
)

type SessionsEnoughCount struct {
	*prometheus.GaugeVec
}

func NewSessionsEnoughCount() *SessionsEnoughCount {
	return &SessionsEnoughCount{
		prometheus.NewGaugeVec(
			prometheus.GaugeOpts{
				Name: "isphere_sessions_enough_count",
			},
			[]string{"source_code"},
		),
	}
}

func (s *SessionsEnoughCount) Register(ctx context.Context) error {
	duration, err := time.ParseDuration(os.Getenv("METRIC_SESSIONS_ENOUGH_COUNT_TIMEOUT_DURATION"))
	if err != nil {
		return errors.Wrap(err, "failed to parse timeout duration")
	}

	prometheus.MustRegister(s)

	go func() {
		doUpdate := func() {
			if err := s.Update(ctx); err != nil {
				logrus.WithContext(ctx).WithError(err).Error("failed to update sessions enough count")
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

func (s *SessionsEnoughCount) Update(ctx context.Context) error {
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
with sess as ( --
    select sourceid,
           count(*) live_sessions
    from session
    where sessionstatusid in (1, 2, 7)
    group by sourceid
    union
    select sourceid,
           count(*) live_sessions
    from session_getcontact
    where sessionstatusid in (1, 2, 7)
    group by sourceid
    union
    select sourceid,
           count(*) live_sessions
    from session_gosuslugi
    where sessionstatusid in (1, 2, 7)
    group by sourceid --
)
select s.code,
       sess.live_sessions < round(s.min_sessions * 0.8)
from source s
         inner join sess on sess.sourceid = s.id
where (s.status = 0
    or (
           s.status = 1
               and id in (select sourceid
                          from sourceaccess)))
  `
	rows, err := db.QueryContext(ctx, query)
	if err != nil {
		return errors.Wrap(err, "failed to query")
	}
	//goland:noinspection GoUnhandledErrorResult
	defer rows.Close()

	for rows.Next() {
		var sourceCode string
		var count float64
		if err = rows.Scan(&sourceCode, &count); err != nil {
			return errors.Wrap(err, "failed to scan")
		}

		s.GaugeVec.
			WithLabelValues(sourceCode).
			Set(count)
	}

	return nil
}
