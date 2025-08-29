package main

import (
	"bytes"
	"fmt"
	"io"
	"net/http"
	"os"
	"time"

	_type "git.i-sphere.ru/isphere-go-modules/ripe/internal/type"
	"github.com/gin-gonic/gin"
	"github.com/google/uuid"
	"github.com/graphql-go/graphql"
	"github.com/graphql-go/handler"
	"github.com/sirupsen/logrus"
)

const DefaultAddr = ":80"

var addr string

func init() {
	if addr = os.Getenv("ADDR"); addr == "" {
		addr = DefaultAddr
	}

	logrus.SetLevel(logrus.DebugLevel)
}

func main() {
	schemaConfig := graphql.SchemaConfig{
		Query: _type.NewQuery(),
	}

	schema, err := graphql.NewSchema(schemaConfig)
	if err != nil {
		logrus.WithError(err).Fatal("failed to build schema")
	}

	engine := gin.Default()
	engine.Use(requestID)
	engine.Use(logger)

	h := handler.New(&handler.Config{
		Schema:     &schema,
		Playground: true,
	})

	engine.POST("/graphql", func(c *gin.Context) {
		h.ContextHandler(c, c.Writer, c.Request)
	})

	if err = engine.Run(addr); err != nil {
		logrus.WithError(err).Fatalf("failed to listen server: %v", err)
	}
}

func requestID(c *gin.Context) {
	reqID := c.GetHeader("X-Request-ID")
	if reqID == "" {
		reqID = uuid.NewString()
	}

	c.Set("x-request-id", reqID)
	c.Next()
}

func logger(c *gin.Context) {
	body, err := io.ReadAll(c.Request.Body)
	if err != nil {
		_ = c.AbortWithError(http.StatusInternalServerError, fmt.Errorf("failed to read request body: %w", err))

		return
	}

	c.Request.Body = io.NopCloser(bytes.NewReader(body))

	w := &responseBodyWriter{
		ResponseWriter: c.Writer,
		body:           &bytes.Buffer{},
	}

	c.Writer = w

	logrus.WithFields(logrus.Fields{
		"remote_addr":     c.RemoteIP(),
		"request_body":    string(body),
		"request_headers": c.Request.Header,
		"request_id":      c.Value("x-request-id"),
		"request_method":  c.Request.Method,
		"request_proto":   c.Request.Proto,
		"request_time":    time.Now().Format(time.RFC3339),
		"request_uri":     c.Request.RequestURI,
	}).Debug("request")

	defer func() {
		logrus.WithFields(logrus.Fields{
			"request_id":       c.Value("x-request-id"),
			"response_body":    w.body.String(),
			"response_headers": c.Writer.Header(),
			"response_status":  c.Writer.Status(),
			"response_time":    time.Now().Format(time.RFC3339),
		}).Debug("response")
	}()

	c.Next()
}

type responseBodyWriter struct {
	gin.ResponseWriter
	body *bytes.Buffer
}

func (t *responseBodyWriter) Write(b []byte) (int, error) {
	t.body.Write(b)

	return t.ResponseWriter.Write(b)
}
