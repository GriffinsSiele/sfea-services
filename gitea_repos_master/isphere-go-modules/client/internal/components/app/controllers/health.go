package controllers

import (
	"net/http"

	"github.com/gin-gonic/gin"

	"git.i-sphere.ru/client/internal/components/app"
	"git.i-sphere.ru/client/internal/components/app/model"
)

type Health struct {
	encoder *app.Encoder
}

func NewHealth(encoder *app.Encoder) *Health {
	return &Health{
		encoder: encoder,
	}
}

func (t *Health) Describe(router *gin.Engine) {
	router.GET("/health", t.Get)
}

func (t *Health) Get(ctx *gin.Context) {
	health := &model.Health{
		Status: model.HealthStatusPass,
	}

	ctx.Status(http.StatusOK)
	t.encoder.Encode(ctx, health)
}
