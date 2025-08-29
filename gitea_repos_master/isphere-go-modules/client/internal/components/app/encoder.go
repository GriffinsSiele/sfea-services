package app

import (
	"encoding/json"

	"github.com/gin-gonic/gin"
	"github.com/sirupsen/logrus"

	"git.i-sphere.ru/client/internal/components/app/model"
)

type Encoder struct {
	env *Env
}

func NewEncoder(env *Env) *Encoder {
	return &Encoder{
		env: env,
	}
}

func (t *Encoder) Encode(ctx *gin.Context, subject any) {
	ctx.Header("Content-Type", model.HealthMimeType)

	if err := t.WithIndentation(json.NewEncoder(ctx.Writer)).Encode(subject); err != nil {
		logrus.WithError(err).Error("failed to encode subject")
	}
}

func (t *Encoder) WithIndentation(marshaller *json.Encoder) *json.Encoder {
	if t.env.Debug {
		marshaller.SetIndent("", "  ")
	}

	return marshaller
}
