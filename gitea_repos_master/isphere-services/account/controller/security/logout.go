package security

import (
	"net/http"

	"github.com/gin-contrib/sessions"
	"github.com/gin-gonic/gin"
	"github.com/sirupsen/logrus"
)

type Logout struct {
}

func NewLogout() *Logout {
	return &Logout{}
}

func (t *Logout) Describe(router *gin.Engine) {
	router.GET("/logout", t.GET)
}

func (t *Logout) GET(ctx *gin.Context) {
	session := sessions.Default(ctx)
	session.Clear()

	if err := session.Save(); err != nil {
		logrus.WithError(err).Error("cannot update session")
	}

	ctx.Redirect(http.StatusFound, "/login")
}
