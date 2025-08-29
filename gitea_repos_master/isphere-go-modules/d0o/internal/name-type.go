package internal

import (
	"github.com/graphql-go/graphql"
)

type NameType struct {
	graphql.Type
}

func NewNameType() *NameType {
	return &NameType{
		Type: graphql.NewObject(graphql.ObjectConfig{
			Name: "name",
			Fields: graphql.Fields{
				"surname": {Type: graphql.String},
				"name":    {Type: graphql.String},
				"count": {
					Type: graphql.Int,
					Resolve: func(p graphql.ResolveParams) (any, error) {
						return int(p.Source.(*Name).Count), nil
					},
				},
			},
		}),
	}
}
