package clickhouse

import (
	"context"
	"fmt"
	"net/http"
	"strconv"
	"strings"

	"go.i-sphere.ru/proxphere/internal/healthcheck"
)

type Clickhouse struct {
	endpoint string
}

func NewClickhouse(endpoint string) *Clickhouse {
	return &Clickhouse{
		endpoint: endpoint,
	}
}

func (c *Clickhouse) Save(ctx context.Context, states healthcheck.States) error {
	var dataBuilder strings.Builder

	dataBuilder.WriteString(`--
insert into proxy_check.logs (proxy_id, dial_duration, connect_duration, response_duration, ip, error)
values 
`,
	)

	for _, state := range states {
		var proxyIDNullable string
		if p := state.Proxy; p != nil {
			proxyIDNullable = strconv.Itoa(p.ID)
		} else {
			proxyIDNullable = "NULL"
		}

		var errorNullable string
		if err := state.Error; err != nil {
			errorNullable = c.quote(err.Error())
		} else {
			errorNullable = "NULL"
		}

		dataBuilder.WriteString(fmt.Sprintf(`
(%s, %d, %d, %d, %s, %s)
`,
			proxyIDNullable,
			state.DialDuration.Milliseconds(),
			state.ConnectDuration.Milliseconds(),
			state.ResponseDuration.Milliseconds(),
			c.quote(state.IP.String()),
			errorNullable,
		))
	}

	req, err := http.NewRequestWithContext(ctx, http.MethodPost, c.endpoint, strings.NewReader(dataBuilder.String()))
	if err != nil {
		return fmt.Errorf("failed to create request: %w", err)
	}

	req.Header.Set("Content-Type", "application/x-www-form-urlencoded")

	resp, err := http.DefaultClient.Do(req)
	if err != nil {
		return fmt.Errorf("failed to do request: %w", err)
	}
	//goland:noinspection GoUnhandledErrorResult
	defer resp.Body.Close()

	if resp.StatusCode != http.StatusOK {
		return fmt.Errorf("failed to save to clickhouse, status: %c", resp.Status)
	}

	return nil
}

func (c *Clickhouse) quote(s string) string {
	return "'" + strings.ReplaceAll(s, "'", "''") + "'"
}
