package security

import (
	"encoding/json"
	"fmt"
	"io"
	"net/http"
	"os"
	"strings"
	"time"

	"git.i-sphere.ru/isphere-services/login/model"
	"git.i-sphere.ru/isphere-services/login/util"
	"github.com/gin-contrib/sessions"
	"github.com/gin-gonic/gin"
	"github.com/sirupsen/logrus"
	csrf "github.com/utrack/gin-csrf"
)

type Login struct {
}

func NewLogin() *Login {
	return &Login{}
}

func (t *Login) Describe(router *gin.Engine) {
	router.
		GET("/login", t.GET).
		POST("/login", t.POST)
}

func (t *Login) GET(ctx *gin.Context) {
	session := sessions.Default(ctx)

	var (
		username = session.Get("username")
		exists   = session.Get("_exists")
		err      = session.Get(model.SessionErrorKey)
	)

	session.Delete("username")
	session.Delete("_exists")
	session.Delete(model.SessionErrorKey)

	if err := session.Save(); err != nil {
		logrus.WithError(err).Error("cannot update session")
	}

	if !strings.Contains(ctx.GetHeader("Referer"), "/login") && (username != nil || exists != nil || err != nil) {
		ctx.Redirect(http.StatusFound, "/login")

		return
	}

	if username != nil && err == nil {
		if exists != nil {
			if util.BoolValue(exists) {
				session.Set("username", util.StringValue(username))

				if err := session.Save(); err != nil {
					logrus.WithError(err).Error("cannot update session")
				}

				ctx.Redirect(http.StatusFound, "/challenge")

				return
			} else {
				err = util.Any("Не удалось найти аккаунт iSphere")
			}
		}
	}

	ctx.HTML(http.StatusOK, "login.html.template", map[string]any{
		"CSRFToken": csrf.GetToken(ctx),
		"Error":     err,
		"Username":  username,
	})
}

func (t *Login) POST(ctx *gin.Context) {
	session := sessions.Default(ctx)

	defer func() {
		if err := session.Save(); err != nil {
			logrus.WithError(err).Error("cannot update session")
		}

		ctx.Redirect(http.StatusFound, "/login")
	}()

	var loginForm struct {
		Username string `form:"username" binding:"required"`
	}

	if err := ctx.ShouldBind(&loginForm); err != nil {
		session.Set(model.SessionErrorKey, fmt.Sprintf("failed to bind login form: %v", err))

		return
	}

	session.Set("username", loginForm.Username)

	resp, err := http.Get(os.Getenv("ISPHERE_MAIN_SERVICE_URL") + "/api/v1/identity/login/" + loginForm.Username)

	if err != nil {
		session.Set(model.SessionErrorKey, fmt.Sprintf("cannot check identity login: %v", err))

		return
	}

	response, err := io.ReadAll(resp.Body)

	if err != nil {
		session.Set(model.SessionErrorKey, fmt.Sprintf("cannot parse identity login response: %v", err))

		return
	}

	//goland:noinspection GoUnhandledErrorResult
	defer resp.Body.Close()

	var responseData struct {
		ID        string     `json:"id"`
		Status    string     `json:"status"`
		CreatedAt *time.Time `json:"created_at"`
	}

	if err := json.Unmarshal(response, &responseData); err != nil {
		session.Set(model.SessionErrorKey, fmt.Sprintf("cannot unmarshal identity login response: %v", err))

		return
	}

	session.Set("_exists", responseData.CreatedAt.Unix()%2 == 0)
}
