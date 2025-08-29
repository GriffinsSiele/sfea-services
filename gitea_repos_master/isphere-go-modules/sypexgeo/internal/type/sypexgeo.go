package _type

import (
	"git.i-sphere.ru/isphere-go-modules/sypexgeo/internal"
	"github.com/graphql-go/graphql"
)

func NewSypexGeoField() *graphql.Field {
	item := NewItem()

	return &graphql.Field{
		Args: graphql.FieldConfigArgument{
			"ip": {
				Type: graphql.NewNonNull(graphql.String),
			},
		},
		Type:    graphql.NewNonNull(graphql.NewList(graphql.NewNonNull(item))),
		Resolve: internal.Resolver,
	}
}
