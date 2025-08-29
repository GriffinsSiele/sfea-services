package internal

import "github.com/graphql-go/graphql"

type NamesField struct {
	*graphql.Field
}

func NewNamesField(namesResolver *NamesResolver, namesType *NameType, telType *TelType) *NamesField {
	return &NamesField{
		Field: &graphql.Field{
			Args: graphql.FieldConfigArgument{
				"phone": {
					Type: graphql.NewNonNull(telType.Scalar),
				},
			},
			Type:    graphql.NewNonNull(graphql.NewList(graphql.NewNonNull(namesType.Type))),
			Resolve: namesResolver.Resolve,
		},
	}
}
