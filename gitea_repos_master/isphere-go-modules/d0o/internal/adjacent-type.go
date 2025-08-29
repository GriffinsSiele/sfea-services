package internal

import (
	"github.com/graphql-go/graphql"
)

type AdjacentType struct {
	graphql.Type
}

func NewAdjacentType(telType *TelType) *AdjacentType {
	return &AdjacentType{
		Type: graphql.NewObject(graphql.ObjectConfig{
			Name: "adjacent",
			Fields: graphql.Fields{
				"phone": {
					Type: graphql.NewNonNull(telType.Scalar),
					Resolve: func(p graphql.ResolveParams) (any, error) {
						return p.Source, nil
					},
				},
			},
		}),
	}
}
