package healthcheck

import (
	"context"
	"database/sql"
	"errors"
	"fmt"
	"time"

	_ "github.com/go-sql-driver/mysql"
	"github.com/google/uuid"
	"i-sphere.ru/healthcheck/internal/contract"
	"i-sphere.ru/healthcheck/internal/env"
	"i-sphere.ru/healthcheck/internal/storage"
)

type MysqlCheckTypeStatuses struct {
	params *env.Params
}

func NewMysqlCheckTypeStatuses(p *env.Params) *MysqlCheckTypeStatuses {
	return &MysqlCheckTypeStatuses{
		params: p,
	}
}

func (s *MysqlCheckTypeStatuses) Name() string {
	return "check-type-statuses"
}

func (s *MysqlCheckTypeStatuses) InspectionInterval() time.Duration {
	return 10 * time.Minute
}

func (s *MysqlCheckTypeStatuses) Destinations() []contract.HealthcheckDestination {
	return []contract.HealthcheckDestination{
		contract.HealthcheckDestinationServer,
	}
}

func (s *MysqlCheckTypeStatuses) Check(ctx context.Context, events *storage.Events) error {
	requestID := uuid.New()
	events.RequestID = &requestID

	dsn := fmt.Sprintf(
		"%s:%s@tcp(%s:%d)/isphere",
		s.params.MainServiceDatabaseUsername,
		s.params.MainServiceDatabasePassword,
		s.params.MainServiceDatabaseHost,
		s.params.MainServiceDatabasePort,
	)
	db, err := sql.Open("mysql", dsn)
	if err != nil {
		return fmt.Errorf("failed to open connection: %w", err)
	}
	//goland:noinspection GoUnhandledErrorResult
	defer db.Close()

	if err = db.PingContext(ctx); err != nil {
		return fmt.Errorf("failed to ping: %w", err)
	}

	allQuery := `select rn.checktype check_type,
       count(*)
from ResponseNew rn
where rn.created_at > (now() - interval 10 minute)
group by check_type`

	allRows, err := db.QueryContext(ctx, allQuery)
	if err != nil {
		return fmt.Errorf("failed to errorQuery: %w", err)
	}
	//goland:noinspection GoUnhandledErrorResult
	defer allRows.Close()

	for allRows.Next() {
		var checkType string
		var allCount int
		if err = allRows.Scan(&checkType, &allCount); err != nil {
			return fmt.Errorf("failed to scan: %w", err)
		}

		events.Append(storage.NewEvent("all", checkType).
			WithDuration(time.Duration(allCount) * time.Nanosecond))
	}

	errorQuery := `select rn.checktype check_type,
       re.text      error_message,
       count(*)     errors_count
from ResponseNew rn
         inner join ResponseError re on re.response_id = rn.id
where rn.created_at > (now() - interval 10 minute)
  and rn.res_code >= 500
group by check_type,
         error_message
order by errors_count desc;`

	errorRows, err := db.QueryContext(ctx, errorQuery)
	if err != nil {
		return fmt.Errorf("failed to query: %w", err)
	}
	//goland:noinspection GoUnhandledErrorResult
	defer errorRows.Close()

	for errorRows.Next() {
		var checkType string
		var errorText string
		var errorsCount int
		if err = errorRows.Scan(&checkType, &errorText, &errorsCount); err != nil {
			return fmt.Errorf("failed to scan: %w", err)
		}

		events.Append(storage.NewEvent("error", checkType).
			WithError(errors.New(errorText)).
			WithDuration(time.Duration(errorsCount) * time.Nanosecond))
	}

	return nil
}
