package internal

import "github.com/graphql-go/graphql"

type QueryType struct {
	*graphql.Object
}

func NewQueryType(resultField *ResultField) *QueryType {
	return &QueryType{
		Object: graphql.NewObject(graphql.ObjectConfig{
			Name: "Query",
			Fields: graphql.Fields{
				"shodan": resultField.Field,
			},
		}),
	}
}
