package internal

import "github.com/graphql-go/graphql"

type ResultField struct {
	*graphql.Field
}

func NewResultField(resultResolver *ResultResolver, itemType *ItemType) *ResultField {
	return &ResultField{
		Field: &graphql.Field{
			Args: graphql.FieldConfigArgument{
				"ip": {
					Type: graphql.NewNonNull(graphql.String),
				},
			},
			Type:    graphql.NewNonNull(graphql.NewList(graphql.NewNonNull(itemType.Union))),
			Resolve: resultResolver.Resolve,
		},
	}
}
