package commands

import (
	"bytes"
	"context"
	"encoding/json"
	"fmt"
	"net/http"
	"os"

	"github.com/charmbracelet/log"
	"github.com/jackc/pgx/v5"

	"i-sphere.ru/proxy/internal/connection"
	"i-sphere.ru/proxy/internal/model"
)

type InitDB struct {
	postgres *connection.Postgres
	log      *log.Logger
}

func NewInitDB(postgres *connection.Postgres) *InitDB {
	return &InitDB{
		postgres: postgres,
		log:      log.WithPrefix("commands.InitDB"),
	}
}

func (t *InitDB) Action(ctx context.Context) error {
	// Create GraphQL query
	// language=graphql
	query := `{
    proxy_proxies {
        id
        server
        port
        login
        password
		proxygroup
        country
    }
}
`
	// Marshal query into request body bytes
	reqBody := map[string]string{"query": query}
	reqBodyBytes, err := json.Marshal(reqBody)
	if err != nil {
		return fmt.Errorf("failed to marshal request body: %w", err)
	}

	// Create HTTP request
	req, err := http.NewRequestWithContext(
		ctx,
		http.MethodPost,
		os.Getenv("HASURA_ENDPOINT"),
		bytes.NewReader(reqBodyBytes),
	)
	if err != nil {
		return fmt.Errorf("failed to create request: %w", err)
	}
	req.Header.Set("X-Hasura-Admin-Secret", os.Getenv("HASURA_ADMIN_SECRET"))

	// Send HTTP request
	resp, err := http.DefaultClient.Do(req)
	if err != nil {
		return fmt.Errorf("failed to send request: %w", err)
	}
	//goland:noinspection GoUnhandledErrorResult
	defer resp.Body.Close()

	// Decode response into proxySpecs
	proxySpecsWrapped := map[string]map[string][]*model.ProxySpec{}
	if err := json.NewDecoder(resp.Body).Decode(&proxySpecsWrapped); err != nil {
		return fmt.Errorf("failed to decode response: %w", err)
	}
	proxySpecs := proxySpecsWrapped["data"]["proxy_proxies"]

	// Create CSV data
	data := make([][]any, len(proxySpecs))
	for i, proxySpec := range proxySpecs {
		data[i] = []any{
			proxySpec.ID,
			proxySpec.Host,
			proxySpec.Port,
			proxySpec.Username,
			proxySpec.Password,
			proxySpec.ProxyGroup,
			proxySpec.RegionCode,
		}
	}

	// Upload data to PostgreSQL
	conn, err := t.postgres.Acquire(ctx)
	if err != nil {
		return fmt.Errorf("failed to acquire connection: %w", err)
	}
	defer conn.Release()

	tx, err := conn.Begin(ctx)
	if err != nil {
		return fmt.Errorf("failed to begin transaction: %w", err)
	}
	//goland:noinspection GoUnhandledErrorResult
	defer tx.Rollback(ctx)

	// Truncate table
	// language=postgresql
	if _, err = tx.Exec(ctx, `truncate table proxy_specs cascade`); err != nil {
		return fmt.Errorf("failed to truncate table: %w", err)
	}

	// Copy data to table
	if _, err = tx.CopyFrom(ctx, pgx.Identifier{"proxy_specs"}, []string{"id", "server", "port", "login", "password", "proxygroup", "country"}, pgx.CopyFromRows(data)); err != nil {
		return fmt.Errorf("failed to copy data: %w", err)
	}

	if err = tx.Commit(ctx); err != nil {
		return fmt.Errorf("failed to commit transaction: %w", err)
	}

	t.log.With("count", len(proxySpecs), "table", "proxy_specs").Info("success")
	return nil
}
