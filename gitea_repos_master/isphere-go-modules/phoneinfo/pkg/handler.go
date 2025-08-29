package pkg

import (
	"bytes"
	"encoding/json"
	"errors"

	"git.i-sphere.ru/isphere-go-modules/phone/pkg/models"
	"git.i-sphere.ru/isphere-go-modules/phone/pkg/phonenumbers"
	"github.com/valyala/fasthttp"
)

var phonesPath = []byte("/api/v1/phones")

type Handler struct {
	phonenumbers *phonenumbers.Phonenumbers
}

func NewHandler(phonenumbers *phonenumbers.Phonenumbers) *Handler {
	return &Handler{
		phonenumbers: phonenumbers,
	}
}

func (t *Handler) HandleFastHTTP(ctx *fasthttp.RequestCtx) {
	ctx.Response.Header.Set("Content-Type", "application/json")

	if !bytes.Equal(ctx.Path(), phonesPath) {
		writeError(ctx, fasthttp.StatusNotFound, errors.New("not found"))
		return
	}

	var input models.Input
	if err := json.Unmarshal(ctx.PostBody(), &input); err != nil {
		writeError(ctx, fasthttp.StatusUnprocessableEntity, err)
		return
	}

	if input.Phone == "" {
		writeError(ctx, fasthttp.StatusUnprocessableEntity, errors.New("non empty `phone` field is required"))
		return
	}

	output, err := t.phonenumbers.Parse(ctx, input.Phone, ctx.QueryArgs().Peek("source"))
	if err != nil {
		writeError(ctx, fasthttp.StatusBadRequest, err)
		return
	}

	serialized, err := json.Marshal(output)
	if err != nil {
		writeError(ctx, fasthttp.StatusInternalServerError, err)
		return
	}

	if _, err = ctx.Write(serialized); err != nil {
		ctx.SetStatusCode(fasthttp.StatusInternalServerError)
		return
	}
}

func writeError(ctx *fasthttp.RequestCtx, statusCode int, err error) {
	ctx.SetStatusCode(statusCode)

	serialized, err := json.Marshal(&models.Error{
		Error: err.Error(),
	})

	if err != nil {
		ctx.SetStatusCode(fasthttp.StatusInternalServerError)
		return
	}

	if _, err = ctx.Write(serialized); err != nil {
		ctx.SetStatusCode(fasthttp.StatusInternalServerError)
	}
}
