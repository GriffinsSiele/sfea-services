package controller

import (
	"github.com/gin-gonic/gin"
)

type SandboxController struct{}

func NewSandboxController() *SandboxController {
	return &SandboxController{}
}

func (t *SandboxController) Describe(router *gin.Engine) {
	router.StaticFile("/sandbox", "./app/build/index.html")
	router.StaticFile("manifest.json", "./app/build/manifest.json")

	router.Static("/static", "./app/build/static")
}
