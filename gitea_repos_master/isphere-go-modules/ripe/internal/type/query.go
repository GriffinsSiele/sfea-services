package _type

import (
	"github.com/graphql-go/graphql"
)

func NewQuery() *graphql.Object {
	return graphql.NewObject(graphql.ObjectConfig{
		Name: "RootQuery",
		Fields: graphql.Fields{
			"ripe": NewRipeField(),
		},
	})
}
