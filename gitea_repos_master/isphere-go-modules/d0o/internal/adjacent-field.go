package internal

import "github.com/graphql-go/graphql"

type AdjacentField struct {
	*graphql.Field
}

func NewAdjacentField(adjacentResolver *AdjacentResolver, adjacentType *AdjacentType, telType *TelType) *AdjacentField {
	return &AdjacentField{
		Field: &graphql.Field{
			Args: graphql.FieldConfigArgument{
				"phone": {
					Type: graphql.NewNonNull(telType.Scalar),
				},
			},
			Type:    graphql.NewNonNull(graphql.NewList(graphql.NewNonNull(adjacentType.Type))),
			Resolve: adjacentResolver.Resolve,
		},
	}
}
