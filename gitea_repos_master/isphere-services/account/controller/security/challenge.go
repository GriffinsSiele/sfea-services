package security

import (
	"encoding/json"
	"fmt"
	"io"
	"net/http"
	"net/url"
	"os"
	"strings"

	"git.i-sphere.ru/isphere-services/login/model"
	"git.i-sphere.ru/isphere-services/login/util"
	"github.com/gin-contrib/sessions"
	"github.com/gin-gonic/gin"
	"github.com/sirupsen/logrus"
	csrf "github.com/utrack/gin-csrf"
)

type Challenge struct {
}

func NewChallenge() *Challenge {
	return &Challenge{}
}

func (t *Challenge) Describe(router *gin.Engine) {
	router.
		GET("/challenge", t.GET).
		POST("/challenge", t.POST)
}

func (t *Challenge) GET(ctx *gin.Context) {
	session := sessions.Default(ctx)

	var (
		username = session.Get("username")
		exists   = session.Get("_exists")
		sub      = session.Get("_sub")
		err      = session.Get(model.SessionErrorKey)
	)

	session.Delete("username")
	session.Delete("_exists")
	session.Delete("_sub")
	session.Delete(model.SessionErrorKey)

	if err := session.Save(); err != nil {
		logrus.WithError(err).Error("cannot update session")
	}

	if username == nil && exists == nil && err == nil {
		ctx.Redirect(http.StatusFound, "/login")

		return
	}

	if strings.Contains(ctx.GetHeader("Referer"), "/login") && (username == nil || exists != nil || err != nil) {
		ctx.Redirect(http.StatusFound, "/login")

		return
	}

	if !strings.Contains(ctx.GetHeader("Referer"), "/challenge") && (exists != nil || err != nil) {
		ctx.Redirect(http.StatusFound, "/challenge")

		return
	}

	if username != nil && err == nil {
		if exists != nil {
			if util.BoolValue(exists) {
				session.Set("subject", util.StringValue(sub))

				if err := session.Save(); err != nil {
					logrus.WithError(err).Error("cannot update session")
				}

				ctx.Redirect(http.StatusFound, util.StringValue(session.Get("redirect_uri")))

				return
			} else {
				err = util.Any("cannot cast identity as map")
			}
		} else {
			err = util.Any("Неверный пароль. Повторите попытку или нажмите на ссылку \"Забыли пароль?\", чтобы сбросить его")
		}
	}

	ctx.HTML(http.StatusOK, "challenge.html.template", map[string]any{
		"CSRFToken": csrf.GetToken(ctx),
		"Error":     err,
		"Username":  username,
	})
}

func (t *Challenge) POST(ctx *gin.Context) {
	session := sessions.Default(ctx)

	defer func() {
		if err := session.Save(); err != nil {
			logrus.WithError(err).Error("cannot update session")
		}

		ctx.Redirect(http.StatusFound, "/challenge")
	}()

	var challengeForm struct {
		Username string `form:"username" binding:"required"`
		Password string `form:"password" binding:"required"`
	}

	if err := ctx.ShouldBind(&challengeForm); err != nil {
		session.Set(model.SessionErrorKey, fmt.Sprintf("failed to bind challenge form: %v", err))

		return
	}

	reqURL, _ := url.Parse(os.Getenv("ISPHERE_MAIN_SERVICE_URL"))
	reqURL.User = url.UserPassword(challengeForm.Username, challengeForm.Password)
	reqURL.Path = "/api/v1/identity"

	resp, err := http.Get(reqURL.String())

	if err != nil {
		session.Set(model.SessionErrorKey, fmt.Sprintf("cannot check identity: %v", err))

		return
	}

	session.Set("username", challengeForm.Username)

	response, err := io.ReadAll(resp.Body)

	if err != nil {
		session.Set(model.SessionErrorKey, fmt.Sprintf("cannot parse identity response: %v", err))

		return
	}

	//goland:noinspection GoUnhandledErrorResult
	defer resp.Body.Close()

	if resp.StatusCode != 200 {
		session.Set("_exists", false)

		return
	}

	var responseData struct {
		Login string `json:"login"`
	}

	if err := json.Unmarshal(response, &responseData); err != nil {
		session.Set(model.SessionErrorKey, fmt.Sprintf("cannot unmrashal identity response: %v", err))

		return
	}

	session.Set("username", responseData.Login)
	session.Set("_exists", true)
	session.Set("_sub", responseData.Login)
}
