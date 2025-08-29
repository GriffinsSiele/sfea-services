package oauth2

import (
	"fmt"
	"net/http"

	"github.com/gin-gonic/gin"
	"github.com/go-oauth2/oauth2/v4/server"
)

type Authorize struct {
	srv *server.Server
}

func NewAuthorize(srv *server.Server) *Authorize {
	return &Authorize{
		srv: srv,
	}
}

func (t *Authorize) Describe(router *gin.Engine) {
	router.GET("/oauth2/authorize", t.GET)
}

func (t *Authorize) GET(ctx *gin.Context) {
	ctx.Writer.Header().Set("Content-Type", "application/json")

	if err := t.srv.HandleAuthorizeRequest(ctx.Writer, ctx.Request); err != nil {
		_ = ctx.AbortWithError(http.StatusBadRequest, fmt.Errorf("failed to handle authorize request: %w", err))

		return
	}
}
