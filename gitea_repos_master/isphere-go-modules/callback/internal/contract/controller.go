package contract

import (
	"github.com/gin-gonic/gin"
)

type Controller interface {
	Describe(*gin.Engine)
}
