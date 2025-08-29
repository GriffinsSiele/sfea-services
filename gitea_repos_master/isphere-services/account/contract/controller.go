package contract

import "github.com/gin-gonic/gin"

type Controller interface {
	Describe(*gin.Engine)
}

type GetController interface {
	GET(*gin.Context)
}

type PostController interface {
	POST(*gin.Context)
}
