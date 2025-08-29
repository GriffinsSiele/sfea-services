package internal

import "github.com/graphql-go/graphql"

type AdsField struct {
	*graphql.Field
}

func NewAdsField(adsResolver *AdsResolver, adType *AdType, telType *TelType) *AdsField {
	return &AdsField{
		Field: &graphql.Field{
			Args: graphql.FieldConfigArgument{
				"phone": {
					Type: graphql.NewNonNull(telType.Scalar),
				},
				"region": {
					Type: graphql.NewNonNull(graphql.String),
				},
			},
			Type:    graphql.NewNonNull(graphql.NewList(graphql.NewNonNull(adType.Type))),
			Resolve: adsResolver.Resolve,
		},
	}
}
