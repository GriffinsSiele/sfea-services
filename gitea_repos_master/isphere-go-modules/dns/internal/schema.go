package internal

import (
	"fmt"

	"github.com/graphql-go/graphql"
)

func NewSchema(queryType *QueryType) (*graphql.Schema, error) {
	schema, err := graphql.NewSchema(graphql.SchemaConfig{
		Query: queryType.Object,
	})
	if err != nil {
		return nil, fmt.Errorf("failed to create GraphQL schema: %w", err)
	}

	return &schema, nil
}
