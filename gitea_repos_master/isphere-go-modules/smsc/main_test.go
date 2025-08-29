package main

import (
	"crypto/md5"
	"encoding/hex"
	"net/http"
	"net/http/httptest"
	"strings"
	"testing"

	"git.i-sphere.ru/isphere-go-modules/ripe/internal/handler"
	"github.com/davecgh/go-spew/spew"
	"github.com/gin-gonic/gin"
	"github.com/joho/godotenv"
)

func TestSend(t *testing.T) {
	t.Parallel()

	_ = godotenv.Load(".env")
	_ = godotenv.Overload(".env.local")

	reqBody := `{"tel": "+79772776278"}`
	c, _ := gin.CreateTestContext(httptest.NewRecorder())
	c.Request = httptest.NewRequest(http.MethodPost, "/api/v1/smsc", strings.NewReader(reqBody))

	hash := md5.Sum([]byte(reqBody))
	messageID := hex.EncodeToString(hash[:])

	c.Request.Header.Set("X-Message-ID", messageID)

	handler.Start(c)

	spew.Dump(c.Writer.Status())
}
