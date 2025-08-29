package _type

import (
	"reflect"

	"git.i-sphere.ru/isphere-go-modules/ripe/internal/union"
	"github.com/graphql-go/graphql"
)

func NewItem() *graphql.Union {
	mapping := map[reflect.Type]*graphql.Object{
		reflect.TypeOf(new(union.InetNum)):      newInetNum(),
		reflect.TypeOf(new(union.Organisation)): newOrganisation(),
		reflect.TypeOf(new(union.Role)):         newRole(),
		reflect.TypeOf(new(union.Route)):        newRoute(),
	}

	types := make([]*graphql.Object, 0, len(mapping))
	for _, gqlType := range mapping {
		types = append(types, gqlType)
	}

	return graphql.NewUnion(graphql.UnionConfig{
		Name:  "item",
		Types: types,
		ResolveType: func(p graphql.ResolveTypeParams) *graphql.Object {
			for gqlType, object := range mapping {
				if reflect.TypeOf(p.Value) == gqlType {
					return object
				}
			}

			return nil
		},
	})
}

func newInetNum() *graphql.Object {
	return graphql.NewObject(graphql.ObjectConfig{
		Name: string(union.ItemTypeInetNum),
		Fields: graphql.Fields{
			"admin_c":       {Type: graphql.String},
			"country":       {Type: graphql.String},
			"created":       {Type: graphql.DateTime},
			"descr":         {Type: graphql.String},
			"inetnum":       {Type: graphql.String},
			"last_modified": {Type: graphql.DateTime},
			"netname":       {Type: graphql.String},
			"remarks":       {Type: graphql.String},
			"source":        {Type: graphql.String},
			"status":        {Type: graphql.String},
			"tech_c":        {Type: graphql.String},
		},
	})
}

func newOrganisation() *graphql.Object {
	return graphql.NewObject(graphql.ObjectConfig{
		Name: string(union.ItemTypeOrganisation),
		Fields: graphql.Fields{
			"abuse_c":       {Type: graphql.String},
			"address":       {Type: graphql.String},
			"admin_c":       {Type: graphql.String},
			"country":       {Type: graphql.String},
			"created":       {Type: graphql.DateTime},
			"fax_no":        {Type: graphql.String},
			"last_modified": {Type: graphql.DateTime},
			"mnt_by":        {Type: graphql.String},
			"mnt_ref":       {Type: graphql.String},
			"organisation":  {Type: graphql.String},
			"org_name":      {Type: graphql.String},
			"org_type":      {Type: graphql.String},
			"phone":         {Type: graphql.String},
			"source":        {Type: graphql.String},
			"tech_c":        {Type: graphql.String},
		},
	})
}

func newRole() *graphql.Object {
	return graphql.NewObject(graphql.ObjectConfig{
		Name: string(union.ItemTypeRole),
		Fields: graphql.Fields{
			"admin_c":       {Type: graphql.String},
			"created":       {Type: graphql.DateTime},
			"last_modified": {Type: graphql.DateTime},
			"mnt_by":        {Type: graphql.String},
			"nic_hdl":       {Type: graphql.String},
			"remarks":       {Type: graphql.String},
			"role":          {Type: graphql.String},
			"source":        {Type: graphql.String},
			"tech_c":        {Type: graphql.String},
		},
	})
}

func newRoute() *graphql.Object {
	return graphql.NewObject(graphql.ObjectConfig{
		Name: string(union.ItemTypeRoute),
		Fields: graphql.Fields{
			"created":       {Type: graphql.DateTime},
			"descr":         {Type: graphql.String},
			"last_modified": {Type: graphql.DateTime},
			"mnt_by":        {Type: graphql.String},
			"origin":        {Type: graphql.String},
			"route":         {Type: graphql.String},
			"source":        {Type: graphql.String},
		},
	})
}
