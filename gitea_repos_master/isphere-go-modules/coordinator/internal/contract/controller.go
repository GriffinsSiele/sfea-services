package contract

import "github.com/gin-gonic/gin"

type Controller interface {
	Describe(*gin.Engine)
}

// ---

type GETController interface {
	GET(ctx *gin.Context)
}

// ---

type POSTController interface {
	POST(*gin.Context)
}
