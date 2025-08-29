package internal

import (
	"strings"

	"github.com/graphql-go/graphql"
)

type AdType struct {
	graphql.Type
}

func NewAdType(telType *TelType) *AdType {
	return &AdType{
		Type: graphql.NewObject(graphql.ObjectConfig{
			Name: "ad",
			Fields: graphql.Fields{
				"title": {
					Type: graphql.String,
				},
				"price": {
					Type: graphql.Float,
					Resolve: func(p graphql.ResolveParams) (any, error) {
						ad := p.Source.(*Ad)

						price := ad.Price
						if strings.Contains(ad.Source, "youla.io") {
							price /= 100
						}

						return price, nil
					},
				},
				"time": {
					Type: graphql.DateTime,
					Resolve: func(p graphql.ResolveParams) (any, error) {
						ad := p.Source.(*Ad)

						if ad.Time == nil {
							return nil, nil
						}

						return ad.Time.Time, nil
					},
				},
				"phone": {
					Type: graphql.NewNonNull(telType.Scalar),
				},
				"name": {
					Type: graphql.String,
				},
				"description": {
					Type: graphql.String,
				},
				"location": {
					Type: graphql.String,
				},
				"source": {
					Type: graphql.String,
				},
				"category": {
					Type: graphql.String,
				},
				"url": {
					Type: graphql.String,
				},
			},
		}),
	}
}
