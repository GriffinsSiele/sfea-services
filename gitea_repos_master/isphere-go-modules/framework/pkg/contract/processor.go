package contract

import (
	"context"

	"git.i-sphere.ru/isphere-go-modules/framework/pkg/model"
)

type Processor interface {
	Process(context.Context, Message) (*model.Response, error)
}
