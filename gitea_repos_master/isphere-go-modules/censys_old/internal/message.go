package internal

import "git.i-sphere.ru/isphere-go-modules/framework/pkg/model"

type Message struct {
	model.Message `validate:"required"`
	IP            string `json:"ip" validate:"required,ipv4"`
}
