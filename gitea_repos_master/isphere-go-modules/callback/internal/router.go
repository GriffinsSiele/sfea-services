package internal

import (
	"git.i-sphere.ru/isphere-go-modules/callback/internal/contract"
	"github.com/gin-gonic/gin"
)

func NewRouter(controllers []contract.Controller) *gin.Engine {
	e := gin.Default()
	for _, controller := range controllers {
		controller.Describe(e)
	}

	return e
}
