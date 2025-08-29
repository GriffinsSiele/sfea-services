package internal

import (
	"bytes"
	"context"
	"fmt"
	"io"
	"net"
	"net/http"
	"time"

	"github.com/gin-gonic/gin"
	"github.com/google/uuid"
	"github.com/graphql-go/handler"
	"github.com/sirupsen/logrus"
	"go.uber.org/fx"
)

func NewServer(lc fx.Lifecycle, config *Config, shutdowner fx.Shutdowner, h *handler.Handler) *http.Server {
	engine := gin.Default()
	engine.Use(requestID)
	engine.Use(logger)
	engine.POST("/graphql", func(c *gin.Context) {
		h.ContextHandler(c, c.Writer, c.Request)
	})

	srv := &http.Server{
		Addr:    config.Addr,
		Handler: engine.Handler(),
	}

	lc.Append(fx.Hook{
		OnStart: func(ctx context.Context) error {
			ln, err := net.Listen("tcp", srv.Addr)
			if err != nil {
				return fmt.Errorf("failed to net listen: %w", err)
			}

			logrus.Debugf("starting HTTP server at: %s", srv.Addr)

			go func() {
				if err = srv.Serve(ln); err != nil {
					logrus.WithError(err).Error("failed to server HTTP")

					//goland:noinspection GoUnhandledErrorResult
					_ = shutdowner.Shutdown()
				}
			}()

			return nil
		},

		OnStop: func(ctx context.Context) error {
			if err := srv.Shutdown(ctx); err != nil {
				return fmt.Errorf("failed to shudtown HTTP server: %w", err)
			}

			return nil
		},
	})

	return srv
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
