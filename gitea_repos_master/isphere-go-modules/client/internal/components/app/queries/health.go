package queries

import "github.com/graphql-go/graphql"

type Health struct{}

func NewHealth() *Health {
	return &Health{}
}

func (t *Health) String() string {
	return "health"
}

func (t *Health) Query() *graphql.Field {
	return &graphql.Field{
		Type: graphql.String,
	}
}
