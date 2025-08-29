package oauth2

import (
	"fmt"
	"net/http"

	"github.com/gin-gonic/gin"
	"github.com/go-oauth2/oauth2/v4/server"
)

type Token struct {
	srv *server.Server
}

func NewToken(srv *server.Server) *Token {
	return &Token{
		srv: srv,
	}
}

func (t *Token) Describe(router *gin.Engine) {
	router.POST("/oauth2/token", t.POST)
}

func (t *Token) POST(ctx *gin.Context) {
	ctx.Writer.Header().Set("Content-Type", "application/json")
	
	if err := t.srv.HandleTokenRequest(ctx.Writer, ctx.Request); err != nil {
		_ = ctx.AbortWithError(http.StatusBadRequest, fmt.Errorf("failed to handle token request: %w", err))

		return
	}
}
