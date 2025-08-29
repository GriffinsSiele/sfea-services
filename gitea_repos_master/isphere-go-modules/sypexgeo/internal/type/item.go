package _type

import (
	"fmt"
	"reflect"

	"git.i-sphere.ru/isphere-go-modules/sypexgeo/internal/model"
	"github.com/graphql-go/graphql"
	"github.com/mitchellh/mapstructure"
)

func NewItem() *graphql.Object {
	var (
		city    = NewCity()
		region  = NewRegion()
		country = NewCountry()
	)

	return graphql.NewObject(graphql.ObjectConfig{
		Name: "sypexgeo",
		Fields: graphql.Fields{
			"city":      {Type: city},
			"country":   {Type: country},
			"created":   {Type: graphql.String},
			"error":     {Type: graphql.String},
			"ip":        {Type: graphql.String},
			"region":    {Type: region},
			"request":   {Type: graphql.Int},
			"timestamp": {Type: graphql.Int},
		},
	})
}

func NewCity() *graphql.Object {
	return graphql.NewObject(graphql.ObjectConfig{
		Name: "city",
		Fields: withLocation(graphql.Fields{
			"population": {Type: graphql.Int},
			"post":       {Type: graphql.String},
			"tel":        {Type: graphql.String},
		}),
	})
}

func NewRegion() *graphql.Object {
	return graphql.NewObject(graphql.ObjectConfig{
		Name: "region",
		Fields: withLocation(graphql.Fields{
			"auto":     {Type: graphql.String},
			"iso":      {Type: graphql.String},
			"timezone": {Type: graphql.String},
			"utc":      {Type: graphql.Int},
		}),
	})
}

func NewCountry() *graphql.Object {
	return graphql.NewObject(graphql.ObjectConfig{
		Name: "country",
		Fields: withLocation(graphql.Fields{
			"area":       {Type: graphql.Int},
			"capital_en": {Type: graphql.String},
			"capital_id": {Type: graphql.Int},
			"capital_ru": {Type: graphql.String},
			"continent":  {Type: graphql.String},
			"cur_code":   {Type: graphql.String},
			"iso":        {Type: graphql.String},
			"neighbours": {Type: graphql.String},
			"phone":      {Type: graphql.String},
			"population": {Type: graphql.Int},
			"timezone":   {Type: graphql.String},
			"utc":        {Type: graphql.Int},
		}),
	})
}

func proxy(p graphql.ResolveParams) (any, error) {
	var (
		sourceRef     = reflect.ValueOf(p.Source)
		locationField = reflect.Indirect(sourceRef).FieldByName("Location")
		location      = locationField.Interface().(model.Location)
	)

	out := make(map[string]any)
	if err := mapstructure.Decode(location, &out); err != nil {
		return nil, fmt.Errorf("failed to decode output: %w", err)
	}

	return out[p.Info.FieldName], nil
}

func withLocation(fields graphql.Fields) graphql.Fields {
	base := graphql.Fields{
		"id":      {Type: graphql.Int, Resolve: proxy},
		"lat":     {Type: graphql.Float, Resolve: proxy},
		"lon":     {Type: graphql.Float, Resolve: proxy},
		"name_de": {Type: graphql.String, Resolve: proxy},
		"name_en": {Type: graphql.String, Resolve: proxy},
		"name_es": {Type: graphql.String, Resolve: proxy},
		"name_fr": {Type: graphql.String, Resolve: proxy},
		"name_it": {Type: graphql.String, Resolve: proxy},
		"name_pt": {Type: graphql.String, Resolve: proxy},
		"name_ru": {Type: graphql.String, Resolve: proxy},
		"name_uk": {Type: graphql.String, Resolve: proxy},
		"okato":   {Type: graphql.String, Resolve: proxy},
		"vk":      {Type: graphql.Int, Resolve: proxy},
	}

	for k, v := range base {
		fields[k] = v
	}

	return fields
}
