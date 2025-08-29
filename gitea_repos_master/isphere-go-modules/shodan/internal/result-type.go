package internal

import (
	"reflect"

	"github.com/graphql-go/graphql"
)

type ItemType struct {
	*graphql.Union
}

func NewItemType(ipItemType *IPItemType, serviceItemType *ServiceItemType) *ItemType {
	mapping := map[reflect.Type]*graphql.Object{
		reflect.TypeOf(new(ResultIP)):      ipItemType.Object,
		reflect.TypeOf(new(ResultService)): serviceItemType.Object,
	}

	types := make([]*graphql.Object, 0, len(mapping))
	for _, gqlType := range mapping {
		types = append(types, gqlType)
	}

	return &ItemType{
		Union: graphql.NewUnion(graphql.UnionConfig{
			Name:  "result",
			Types: types,
			ResolveType: func(p graphql.ResolveTypeParams) *graphql.Object {
				for typ, object := range mapping {
					if reflect.TypeOf(p.Value) == typ {
						return object
					}
				}

				return nil
			},
		}),
	}
}

// ---

type IPItemType struct {
	*graphql.Object
}

func NewIPItemType() *IPItemType {
	return &IPItemType{
		Object: graphql.NewObject(graphql.ObjectConfig{
			Name: "ip",
			Fields: graphql.Fields{
				"ip": {
					Type: graphql.NewNonNull(graphql.String),
				},
				"country_code": {
					Type: graphql.String,
				},
				"country": {
					Type: graphql.String,
				},
				"city": {
					Type: graphql.String,
				},
				"location": {
					Type: graphql.NewObject(graphql.ObjectConfig{
						Name: "location",
						Fields: graphql.Fields{
							"coords": {Type: graphql.NewNonNull(graphql.NewList(graphql.Float))},
							"text":   {Type: graphql.String},
						},
					}),
				},
				"organization": {
					Type: graphql.String,
				},
				"provider": {
					Type: graphql.String,
				},
				"asn": {
					Type: graphql.String,
				},
				"hostnames": {
					Type: graphql.NewNonNull(graphql.NewList(graphql.NewNonNull(graphql.String))),
				},
				"os": {
					Type: graphql.String,
				},
				"ports": {
					Type: graphql.NewNonNull(graphql.NewList(graphql.NewNonNull(graphql.Int))),
				},
				"tags": {
					Type: graphql.NewNonNull(graphql.NewList(graphql.NewNonNull(graphql.String))),
				},
			},
		}),
	}
}

// ---

type ServiceItemType struct {
	*graphql.Object
}

func NewServiceItemType() *ServiceItemType {
	return &ServiceItemType{
		Object: graphql.NewObject(graphql.ObjectConfig{
			Name: "service",
			Fields: graphql.Fields{
				"service": {
					Type: graphql.NewNonNull(graphql.String),
				},
				"port": {
					Type: graphql.NewNonNull(graphql.Int),
				},
				"transport": {
					Type: graphql.String,
				},
				"product": {
					Type: graphql.String,
				},
				"version": {
					Type: graphql.String,
				},
				"tags": {
					Type: graphql.NewNonNull(graphql.NewList(graphql.NewNonNull(graphql.String))),
				},
			},
		}),
	}
}
