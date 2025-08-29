package contracts

import (
	"github.com/gin-gonic/gin"
	"go.uber.org/fx"
)

const ControllerTag = `group:"controllers"`

type Controller interface {
	Describe(*gin.Engine)
}

func AsController(t any) any {
	return fx.Annotate(t, fx.As(new(Controller)), fx.ResultTags(ControllerTag))
}

type GetController interface {
	Get(*gin.Context)
}

type PostController interface {
	Post(*gin.Context)
}

type AnyController interface {
	Any(*gin.Context)
}
