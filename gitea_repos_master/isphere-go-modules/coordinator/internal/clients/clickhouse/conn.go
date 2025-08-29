package clickhouse

import (
	"context"
	"time"

	"gitea-http.gitea.svc.cluster.local/isphere-go-modules/coordinator/internal/model"
	"github.com/ClickHouse/clickhouse-go/v2/lib/driver"
	"github.com/sirupsen/logrus"
)

type Conn struct {
	driver.Conn
}

func (c *Conn) Release() {}

func (c *Conn) PushApplyLogOptions(ctx context.Context, options *model.ApplyLogOptions) {
	if err := c.Exec(ctx,
		// language=clickhouse
		`--
insert into coordinator.logs (scope, status_code, error, start_time, end_time)
values (?, ?, ?, ?, ?)`,
		options.Scope,
		options.StatusCode,
		c.errorAsStringPtr(options.Error),
		options.StartTime,
		c.timeAsNullableDateTime(options.EndTime),
	); err != nil {
		logrus.WithContext(ctx).WithError(err).Error("failed to push apply log options")
	}
}

func (c *Conn) errorAsStringPtr(err error) any {
	if err == nil {
		return nil
	}

	return err.Error()
}

func (c *Conn) timeAsNullableDateTime(v *time.Time) any {
	if v == nil {
		return nil
	}

	return *v
}
