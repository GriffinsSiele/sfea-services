package strategies

import (
	"context"
	"fmt"
	"time"

	http "github.com/Danny-Dasilva/fhttp"
	builder "github.com/Masterminds/squirrel"
	"go.i-sphere.ru/proxy/pkg/clients"
	"go.i-sphere.ru/proxy/pkg/models"
	"go.i-sphere.ru/proxy/pkg/utils"
)

type NotFail struct {
	clickhouse *clients.Clickhouse
}

func NewNotFail(clickhouse *clients.Clickhouse) *NotFail {
	return &NotFail{
		clickhouse: clickhouse,
	}
}

func (f *NotFail) Name() string {
	return "not_fail"
}

func (f *NotFail) Reorder(ctx context.Context, proxies []*models.ProxySpec, params ...string) ([]*models.ProxySpec, error) {
	if len(proxies) == 0 {
		return nil, nil
	}

	limit := utils.FirstParamAsIntWithMax(len(proxies), params...)
	if limit == 0 {
		limit = 1
	}

	duration := utils.SecondParamAsDuration(params...)
	if duration == 0 {
		duration = 1 * time.Hour
	}

	qb := builder.StatementBuilder.PlaceholderFormat(builder.Question)
	q := qb.Select("l.proxy_spec_id").
		From("proxy_spec_logs_direct l").
		Where(builder.Or{
			builder.Expr("l.response_status_code < ?", http.StatusOK),
			builder.Expr("l.response_status_code > ?", http.StatusBadRequest-1),
		}).
		Where("l.timestamp > toDateTime(?, 'Europe/Moscow')", time.Now().Add(-duration).Format(time.DateTime)).
		GroupBy("l.proxy_spec_id").
		OrderBy("max(l.timestamp)")

	if h, ok := ctx.Value("request_host").(string); ok {
		q = q.Where(builder.Eq{"l.request_host": h})
	}

	var ids []int
	for _, p := range proxies {
		ids = append(ids, int(p.ID))
	}
	if len(ids) > 0 {
		q = q.Where(builder.Eq{"l.proxy_spec_id": ids})
	}

	sql, args, err := q.ToSql()
	if err != nil {
		return nil, fmt.Errorf("failed to build sql query: %w", err)
	}

	rows, err := f.clickhouse.QueryContext(ctx, sql, args...)
	if err != nil {
		return nil, fmt.Errorf("failed to execute query: %w", err)
	}

	lookup := make(map[int]bool)
	for rows.Next() {
		var id int
		if errScan := rows.Scan(&id); errScan != nil {
			return nil, fmt.Errorf("failed to scan row: %w", errScan)
		}
		lookup[id] = true
	}

	passed := make([]*models.ProxySpec, 0, len(proxies))
	for _, p := range proxies {
		if !lookup[int(p.ID)] {
			passed = append(passed, p)
		}
	}

	return passed, nil
}
