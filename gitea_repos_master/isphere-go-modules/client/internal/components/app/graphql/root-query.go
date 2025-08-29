package graphql

import (
	"github.com/graphql-go/graphql"

	"git.i-sphere.ru/client/internal/contracts"
)

type RootQuery struct {
	*graphql.Object
}

func NewRootQuery(queriers []contracts.Querier) *RootQuery {
	fields := make(graphql.Fields, len(queriers))
	for _, querier := range queriers {
		fields[querier.String()] = querier.Query()
	}

	return &RootQuery{
		Object: graphql.NewObject(graphql.ObjectConfig{
			Name:   "root_query",
			Fields: fields,
		}),
	}
}
