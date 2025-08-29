package app

import (
	"github.com/gin-gonic/gin"
	"github.com/sirupsen/logrus"
	"github.com/toorop/gin-logrus"

	"git.i-sphere.ru/client/internal/contracts"
)

func NewRouter(controllers []contracts.Controller) *gin.Engine {
	router := gin.New()
	router.Use(
		ginlogrus.Logger(logrus.StandardLogger()),
		gin.Recovery(),
	)

	for _, controller := range controllers {
		controller.Describe(router)
	}

	return router
}
