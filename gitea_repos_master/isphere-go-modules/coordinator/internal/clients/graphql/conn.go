package graphql

import (
	"context"
	"encoding/json"
	"errors"
	"fmt"
	"strings"

	errs "gitea-http.gitea.svc.cluster.local/isphere-go-modules/coordinator/internal/error"
	"github.com/hasura/go-graphql-client"
)

type Conn struct {
	*graphql.Client
	Endpoint string
}

func (c *Conn) Release() {}

func (c *Conn) Exec(
	ctx context.Context,
	query string,
	res any,
	variables map[string]any,
	options ...graphql.Option,
) error {
	resp, err := c.Client.ExecRaw(ctx, query, variables, options...)
	if err != nil {
		var graphqlErrs graphql.Errors
		if errors.As(err, &graphqlErrs) {
			for _, graphqlErr := range graphqlErrs {
				if strings.Contains(graphqlErr.Message, "not found") {
					return errs.UnprocessableEntityError
				}
			}
		}

		return fmt.Errorf("failed to execute GraphQL request: %w", err)
	}

	if err = json.Unmarshal(resp, &res); err != nil {
		return fmt.Errorf("failed to unmarshal response: %w", err)
	}

	return nil
}
