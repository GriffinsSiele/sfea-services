package repository

import (
	"context"
	"errors"
	"fmt"
	"net"
	"net/http"
	"time"

	"github.com/charmbracelet/log"
	"github.com/doug-martin/goqu/v9"
	_ "github.com/doug-martin/goqu/v9/dialect/postgres"
	"golang.org/x/sync/errgroup"

	"i-sphere.ru/proxy/internal/connection"
	"i-sphere.ru/proxy/internal/model"
	"i-sphere.ru/proxy/internal/util"
)

type Proxy struct {
	postgres *connection.Postgres
	log      *log.Logger
}

func NewProxy(postgres *connection.Postgres) *Proxy {
	return &Proxy{
		postgres: postgres,
		log:      log.WithPrefix("repository.Proxy"),
	}
}

func (t *Proxy) Update(ctx context.Context) error {
	conn, err := t.postgres.Acquire(ctx)
	if err != nil {
		return fmt.Errorf("failed to acquire connection: %w", err)
	}
	defer conn.Release()

	// language=postgresql
	rows, err := conn.Query(ctx, `select s.id,
       s.server,
       s.port,
       s.login,
       s.password,
       s.country
from proxy_specs s`)
	if err != nil {
		return fmt.Errorf("failed to query: %w", err)
	}

	var specs []*model.ProxySpec
	for rows.Next() {
		var spec model.ProxySpec
		if err := rows.Scan(&spec.ID, &spec.Host, &spec.Port, &spec.Username, &spec.Password, &spec.RegionCode); err != nil {
			return fmt.Errorf("failed to scan: %w", err)
		}
		specs = append(specs, &spec)
	}

	var wg errgroup.Group
	wg.SetLimit(20)

	disabledSpecsIDs := make([]int, 0)
	enabledSpecIDs := make([]int, 0)

	for i := range specs {
		i := i
		wg.Go(func() error {
			spec := specs[i]
			l := t.log.With("addr", spec.Addr())

			ctx, cancel := context.WithTimeout(ctx, 3*time.Second)
			defer cancel()

			dial, err := (&net.Dialer{}).DialContext(ctx, "tcp", spec.Addr())
			if err != nil {
				l.With("err", err).Warn("failed to dial")
				disabledSpecsIDs = append(disabledSpecsIDs, spec.ID)
				return nil
			}
			//goland:noinspection GoUnhandledErrorResult
			dial.Close()

			transport := &http.Transport{Proxy: http.ProxyURL(spec.URL())}
			client := &http.Client{Transport: transport}
			req, err := http.NewRequestWithContext(
				ctx,
				http.MethodGet,
				"https://i-sphere.ru/2.00/ping.php",
				http.NoBody,
			)
			if err != nil {
				l.With("err", err).Warn("failed to create request")
				disabledSpecsIDs = append(disabledSpecsIDs, spec.ID)
				return nil
			}

			resp, err := client.Do(req)
			if err != nil {
				l.With("err", err).Warn("failed to do request")
				disabledSpecsIDs = append(disabledSpecsIDs, spec.ID)
				return nil
			}
			//goland:noinspection GoUnhandledErrorResult
			defer resp.Body.Close()

			enabledSpecIDs = append(enabledSpecIDs, spec.ID)
			return nil
		})
	}

	if err = wg.Wait(); err != nil {
		return fmt.Errorf("failed to update proxy specs: %w", err)
	}

	if len(enabledSpecIDs) > 0 {
		// language=postgresql
		if _, err = conn.Exec(ctx, `update proxy_specs
set enabled = true
where id = any ($1)`, enabledSpecIDs); err != nil {
			return fmt.Errorf("failed to update proxy specs: %w", err)
		}
	}

	if len(disabledSpecsIDs) > 0 {
		// language=postgresql
		if _, err = conn.Exec(ctx, `update proxy_specs
set enabled = false
where id = any ($1)`, disabledSpecsIDs); err != nil {
			return fmt.Errorf("failed to update proxy specs: %w", err)
		}
	}

	t.log.With(
		"enabledCount", len(enabledSpecIDs),
		"disabledCount", len(disabledSpecsIDs),
	).Info("proxy specs updated")
	return nil
}

func (t *Proxy) Find(ctx context.Context, id int) (*model.ProxySpec, error) {
	conn, err := t.postgres.Acquire(ctx)
	if err != nil {
		return nil, fmt.Errorf("failed to acquire postgres connection: %w", err)
	}
	defer conn.Release()

	var spec model.ProxySpec
	// language=postgresql
	if err = conn.QueryRow(ctx, `select s.id,
       s.server,
       s.port,
       s.login,
       s.password,
       s.country,
       s.enabled
from proxy_specs s
where id = $1`, id).Scan(&spec.ID, &spec.Host, &spec.Port, &spec.Username, &spec.Password, &spec.RegionCode, &spec.Enabled); err != nil {
		return nil, fmt.Errorf("failed to query: %w", err)
	}
	if !spec.Enabled {
		return nil, errors.New("proxy is disabled")
	}

	return &spec, nil
}

func (t *Proxy) FindByRequestContext(ctx *util.Context, enabled bool) ([]*model.ProxySpec, error) {
	conn, err := t.postgres.Acquire(ctx)
	if err != nil {
		return nil, fmt.Errorf("failed to acquire postgres connection: %w", err)
	}
	defer conn.Release()

	qb := goqu.
		From(goqu.T("proxy_specs").As("s")).
		Select(
			goqu.T("s").Col("id"),
			goqu.T("s").Col("server"),
			goqu.T("s").Col("port"),
			goqu.T("s").Col("login"),
			goqu.T("s").Col("password"),
			goqu.T("s").Col("country"),
			goqu.T("s").Col("enabled"),
		).
		Where(goqu.T("s").Col("enabled").IsTrue())

	if ctx.Host != "" {
		qb = qb.Where(goqu.L("NOT EXISTS ?", goqu.
			From(goqu.T("proxy_specs_logs").As("l")).
			Select(goqu.L("1")).
			Where(
				goqu.T("l").Col("proxy_spec_id").Eq(goqu.T("s").Col("id")),
				goqu.T("l").Col("host").Eq(ctx.Host),
				goqu.And(
					goqu.T("l").Col("status_code").Lte(200),
					goqu.T("l").Col("status_code").Gt(399),
				),
				goqu.T("l").
					Col("created_at").
					Gte(time.Now().Add(-15*time.Minute)),
			),
		))
	}

	if ctx.ProxyGroup > 0 {
		qb = qb.Where(goqu.T("s").Col("proxygroup").Eq(ctx.ProxyGroup))
	}
	if ctx.RegionCode != "" {
		qb = qb.Where(goqu.T("s").Col("country").Eq(ctx.RegionCode))
	}

	sql, _, err := qb.ToSQL()
	if err != nil {
		return nil, fmt.Errorf("failed to build query: %w", err)
	}

	rows, err := conn.Query(ctx, sql)
	if err != nil {
		return nil, fmt.Errorf("failed to query: %w", err)
	}

	var specs []*model.ProxySpec
	for rows.Next() {
		var spec model.ProxySpec
		if err := rows.Scan(&spec.ID, &spec.Host, &spec.Port, &spec.Username, &spec.Password, &spec.RegionCode, &spec.Enabled); err != nil {
			return nil, fmt.Errorf("failed to scan: %w", err)
		}
		specs = append(specs, &spec)
	}

	return specs, nil
}
