package main

import (
	"encoding/json"
	"net/http"
	"net/http/httptest"
	"net/url"
	"strings"
	"testing"

	"git.i-sphere.ru/isphere-services/login/controller/oauth2"
	"github.com/davecgh/go-spew/spew"
	"github.com/gin-gonic/gin"
	"github.com/stretchr/testify/assert"
)

const (
	clientID     = "ac8d88f2-a591-4e9b-a079-d06c7c60790e"
	clientSecret = "cffbdd3a-fb6b-4cee-b611-c2b1fb269522"
)

func TestOAuth2ClientCredentials(t *testing.T) {
	t.Parallel()

	assert.NoError(t, load())

	gin.SetMode(gin.TestMode)

	container, err := NewContainer()

	assert.NoError(t, err)

	assert.NoError(t, container.Invoke(func(token *oauth2.Token) {
		requestData := url.Values{
			"grant_type":    []string{"client_credentials"},
			"client_id":     []string{clientID},
			"client_secret": []string{clientSecret},
		}

		var (
			res    = httptest.NewRecorder()
			ctx, _ = gin.CreateTestContext(res)
		)

		ctx.Request = httptest.NewRequest(http.MethodPost, "/oauth2/token", strings.NewReader(requestData.Encode()))
		ctx.Request.Header.Set("Content-Type", "application/x-www-form-urlencoded")

		token.POST(ctx)

		assert.Equal(t, 200, res.Code)

		var responseData struct {
			AccessToken string `json:"access_token"`
			ExpiresIn   int    `json:"expires_in"`
			TokenType   string `json:"token_type"`
		}

		assert.NoError(t, json.Unmarshal(res.Body.Bytes(), &responseData))
		assert.NotEmpty(t, responseData.AccessToken)
		assert.Greater(t, responseData.ExpiresIn, 0)
		assert.Equal(t, responseData.TokenType, "Bearer")
	}))
}

func TestOAuth2Password(t *testing.T) {
	t.Parallel()

	assert.NoError(t, load())

	gin.SetMode(gin.TestMode)

	container, err := NewContainer()

	assert.NoError(t, err)

	assert.NoError(t, container.Invoke(func(token *oauth2.Token) {
		requestData := url.Values{
			"grant_type":    []string{"password"},
			"client_id":     []string{clientID},
			"client_secret": []string{clientSecret},
			"username":      []string{"sk"},
			"password":      []string{"L0sVq$xA"},
		}

		var (
			res    = httptest.NewRecorder()
			ctx, _ = gin.CreateTestContext(res)
		)

		ctx.Request = httptest.NewRequest(http.MethodPost, "/oauth2/token", strings.NewReader(requestData.Encode()))
		ctx.Request.Header.Set("Content-Type", "application/x-www-form-urlencoded")

		token.POST(ctx)

		assert.Equal(t, 200, res.Code)

		var responseData struct {
			AccessToken  string `json:"access_token"`
			RefreshToken string `json:"refresh_token"`
			ExpiresIn    int    `json:"expires_in"`
			TokenType    string `json:"token_type"`
		}

		assert.NoError(t, json.Unmarshal(res.Body.Bytes(), &responseData))
		assert.NotEmpty(t, responseData.AccessToken)
		assert.NotEmpty(t, responseData.RefreshToken)
		assert.Greater(t, responseData.ExpiresIn, 0)
		assert.Equal(t, responseData.TokenType, "Bearer")
	}))
}

func TestOAuth2Authorize(t *testing.T) {
	t.Parallel()

	assert.NoError(t, load())

	gin.SetMode(gin.TestMode)

	container, err := NewContainer()

	assert.NoError(t, err)

	assert.NoError(t, container.Invoke(func(authorize *oauth2.Authorize) {
		// create login request
		queryParams := url.Values{
			"client_id":     []string{clientID},
			"response_type": []string{"code"},
		}

		var (
			res    = httptest.NewRecorder()
			ctx, _ = gin.CreateTestContext(res)
		)

		ctx.Request = httptest.NewRequest(http.MethodGet, "/oauth2/authorize?"+queryParams.Encode(), nil)

		authorize.GET(ctx)

		assert.Equal(t, 200, res.Code)

		location := res.Header().Get("Location")

		assert.Equal(t, location, "/login")

		// submit login form
		spew.Dump(res.Body)
	}))
}
