package oauth2

import (
	"context"
	"encoding/json"
	"fmt"
	"io"
	"net/http"
	"net/url"
	"os"

	"git.i-sphere.ru/isphere-services/login/util"
	"github.com/gin-contrib/sessions"
	"github.com/gin-gonic/gin"
	"github.com/go-oauth2/oauth2/v4/errors"
	"github.com/go-oauth2/oauth2/v4/manage"
	"github.com/go-oauth2/oauth2/v4/server"
	"github.com/sirupsen/logrus"
)

func NewServer(
	config *server.Config,
	manager *manage.Manager,
) *server.Server {
	srv := server.NewServer(config, manager)

	srv.SetAllowGetAccessRequest(true)

	srv.SetClientInfoHandler(server.ClientFormHandler)

	srv.SetInternalErrorHandler(func(err error) *errors.Response {
		logrus.WithError(err).Errorf("oauth2 internal server error")

		return nil
	})

	srv.SetResponseErrorHandler(func(re *errors.Response) {
		logrus.WithError(re.Error).Error("oauth2 error")
	})

	srv.SetPasswordAuthorizationHandler(passwordAuthorizationHandler)
	
	srv.SetUserAuthorizationHandler(userAuthorizationHandler)

	return srv
}

func passwordAuthorizationHandler(ctx context.Context, clientID, username, password string) (string, error) {
	reqURL, _ := url.Parse(os.Getenv("ISPHERE_MAIN_SERVICE_URL"))
	reqURL.User = url.UserPassword(username, password)
	reqURL.Path = "/api/v1/identity"

	resp, err := http.Get(reqURL.String())

	if err != nil {
		return "", fmt.Errorf("cannot check credentials: %w", err)
	}

	response, err := io.ReadAll(resp.Body)

	if err != nil {
		return "", fmt.Errorf("cannot read credentials: %w", err)
	}

	//goland:noinspection GoUnhandledErrorResult
	defer resp.Body.Close()

	if resp.StatusCode != 200 {
		return "", fmt.Errorf("invalid credentials: %w", err)
	}

	var responseData struct {
		Login string `json:"login"`
	}

	if err := json.Unmarshal(response, &responseData); err != nil {
		return "", fmt.Errorf("cannot unmarshal credentials: %w", err)
	}

	return responseData.Login, nil
}

func userAuthorizationHandler(res http.ResponseWriter, req *http.Request) (string, error) {
	ginContext, ok := req.Context().Value("gin").(*gin.Context)
	if !ok {
		return "", fmt.Errorf("gin environment not found")
	}

	var (
		session = sessions.Default(ginContext)
		subject = session.Get("subject")
	)

	if subject != nil {
		return util.StringValue(subject), nil
	}

	session.Set("redirect_uri", req.RequestURI)

	if err := session.Save(); err != nil {
		logrus.WithError(err).Errorf("cannot save session: %v", err)
	}

	res.Header().Set("Location", "/login")
	res.WriteHeader(http.StatusFound)

	return "", nil
}
