package security

import (
	"net/http"

	"git.i-sphere.ru/isphere-services/login/util"
	"github.com/gin-contrib/sessions"
	"github.com/gin-gonic/gin"
)

type Default struct {
}

func NewDefault() *Default {
	return &Default{}
}

func (t *Default) Describe(engine *gin.Engine) {
	engine.GET("/", t.GET)
}

func (t *Default) GET(ctx *gin.Context) {
	var (
		session = sessions.Default(ctx)
		subject = session.Get("subject")
	)

	if subject == nil {
		ctx.Redirect(http.StatusFound, "/login")

		return
	}

	ctx.HTML(http.StatusOK, "default.html.template", map[string]any{
		"Username": util.StringValue(subject),
	})
}
