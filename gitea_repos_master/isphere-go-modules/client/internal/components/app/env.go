package app

import "github.com/gin-gonic/gin"

type Env struct {
	Debug bool
}

func NewEnv() *Env {
	return &Env{
		Debug: gin.Mode() == gin.DebugMode,
	}
}
