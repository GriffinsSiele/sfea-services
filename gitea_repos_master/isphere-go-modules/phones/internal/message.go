package internal

import "git.i-sphere.ru/isphere-go-modules/framework/pkg/model"

type Message struct {
	model.Message `validate:"required"`
	Phone         string `json:"phone" validate:"required,phone=RU"`
}
