package contract

import (
	"context"
	"git.i-sphere.ru/isphere-go-modules/framework/pkg/model"
)

type Storage interface {
	Exists(context.Context, string) (bool, error)
	Find(context.Context, string) (*model.Response, error)
	Persist(context.Context, string, *model.Response) (bool, error)
	Remove(context.Context, string) (bool, error)
}
