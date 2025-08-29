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

type Pass struct {
	clickhouse *clients.Clickhouse
}

func NewPass(clickhouse *clients.Clickhouse) *Pass {
	return &Pass{
		clickhouse: clickhouse,
	}
}

func (f *Pass) Name() string {
	return "pass"
}

func (f *Pass) Reorder(ctx context.Context, proxies []*models.ProxySpec, params ...string) ([]*models.ProxySpec, error) {
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
		Where("l.response_status_code between ? and ?", http.StatusOK, http.StatusBadRequest-1).
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

	reordered := make([]*models.ProxySpec, 0, len(proxies))
	foundIDs := make([]int, 0, len(proxies))

	for rows.Next() {
		var id int
		if errScan := rows.Scan(&id); errScan != nil {
			return nil, fmt.Errorf("failed to scan row: %w", errScan)
		}
		foundIDs = append(foundIDs, id)
		for _, p := range proxies {
			if int(p.ID) == id {
				reordered = append(reordered, p)
				break
			}
		}
	}

	for _, p := range proxies {
		found := false
		for _, id := range foundIDs {
			if int(p.ID) == id {
				found = true
				break
			}
		}
		if !found {
			reordered = append(reordered, p)
		}
	}

	return reordered, nil
}
