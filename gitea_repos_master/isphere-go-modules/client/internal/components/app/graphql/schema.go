package graphql

import (
	"fmt"

	"github.com/graphql-go/graphql"
)

func NewSchema(rootQuery *RootQuery) (*graphql.Schema, error) {
	schema, err := graphql.NewSchema(graphql.SchemaConfig{
		Query: rootQuery.Object,
	})
	if err != nil {
		return nil, fmt.Errorf("failed to make graphql schema: %w", err)
	}

	return &schema, nil
}
