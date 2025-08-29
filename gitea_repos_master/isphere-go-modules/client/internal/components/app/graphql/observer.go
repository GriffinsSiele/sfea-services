package graphql

import (
	"github.com/graphql-go/graphql"
	"github.com/graphql-go/handler"

	"git.i-sphere.ru/client/internal/components/app"
)

func NewObserver(env *app.Env, schema *graphql.Schema) *handler.Handler {
	return handler.New(&handler.Config{
		Pretty: env.Debug,
		Schema: schema,
	})
}
