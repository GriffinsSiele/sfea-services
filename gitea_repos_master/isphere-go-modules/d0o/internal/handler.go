package internal

import (
	"github.com/graphql-go/graphql"
	"github.com/graphql-go/handler"
)

func NewHandler(config *Config, schema *graphql.Schema) *handler.Handler {
	return handler.New(&handler.Config{
		Playground: config.Debug,
		Schema:     schema,
	})
}
