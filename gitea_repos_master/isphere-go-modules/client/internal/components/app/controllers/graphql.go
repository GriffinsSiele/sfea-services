package controllers

import (
	"github.com/gin-gonic/gin"
	"github.com/graphql-go/handler"

	"git.i-sphere.ru/client/internal/components/app"
)

type GraphQL struct {
	encoder  *app.Encoder
	observer *handler.Handler
}

func NewGraphQL(encoder *app.Encoder, observer *handler.Handler) *GraphQL {
	return &GraphQL{
		encoder:  encoder,
		observer: observer,
	}
}

func (t *GraphQL) Describe(router *gin.Engine) {
	router.Any("/graphql", t.Any)
}

func (t *GraphQL) Any(ctx *gin.Context) {
	t.observer.ContextHandler(ctx, ctx.Writer, ctx.Request)
}
