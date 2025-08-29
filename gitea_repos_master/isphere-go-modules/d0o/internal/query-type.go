package internal

import "github.com/graphql-go/graphql"

type QueryType struct {
	*graphql.Object
}

func NewQueryType(adjacentField *AdjacentField, adsField *AdsField, namesField *NamesField) *QueryType {
	return &QueryType{
		Object: graphql.NewObject(graphql.ObjectConfig{
			Name: "Query",
			Fields: graphql.Fields{
				"d0o": {
					Type: graphql.NewNonNull(graphql.NewObject(graphql.ObjectConfig{
						Name: "d0o",
						Fields: graphql.Fields{
							"adjacent": adjacentField.Field,
							"ads":      adsField.Field,
							"names":    namesField.Field,
						},
					})),
					Resolve: func(p graphql.ResolveParams) (interface{}, error) {
						return new(any), nil
					},
				},
			},
		}),
	}
}
