package internal

import (
	"github.com/graphql-go/graphql"
)

type ItemType struct {
	*graphql.Object
}

func NewItemType(resultResolver *ResultResolver) *ItemType {
	return &ItemType{
		Object: graphql.NewObject(graphql.ObjectConfig{
			Name: "result",
			Fields: graphql.Fields{
				"name": {
					Type: graphql.String,
				},
				"hosts": {
					Type:    graphql.NewList(graphql.String),
					Resolve: resultResolver.ResolveHosts,
				},
			},
		}),
	}
}
