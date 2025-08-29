package controller

import (
	"bytes"
	"io"
	"net/http"

	"git.i-sphere.ru/isphere-go-modules/callback/internal/client"
	"github.com/charmbracelet/log"
	"github.com/gin-gonic/gin"
	"github.com/google/uuid"

	"git.i-sphere.ru/isphere-go-modules/callback/internal/connection"
	"git.i-sphere.ru/isphere-go-modules/callback/internal/net"
	"git.i-sphere.ru/isphere-go-modules/callback/internal/util"
)

type DefaultController struct {
	amqp     *connection.AMQP
	balancer *net.Balancer

	log *log.Logger
}

func NewDefaultController(
	amqp *connection.AMQP,
	balancer *net.Balancer,
) *DefaultController {
	return &DefaultController{
		amqp:     amqp,
		balancer: balancer,

		log: log.WithPrefix("controller.DefaultController"),
	}
}

func (t *DefaultController) Describe(e *gin.Engine) {
	e.NoRoute(t.ANY)
}

func (t *DefaultController) ANY(c *gin.Context) {
	tracer, closer := client.MustTracerCloser()
	defer client.MustClose(closer)

	c, span := client.StartSpanWithGinContext(c, tracer, "received request")
	defer span.Finish()

	span.LogKV("remote_addr", c.ClientIP())
	span.LogKV("request_method", c.Request.Method)
	span.LogKV("request_uri", c.Request.URL)
	for header, value := range c.Request.Header {
		span.LogKV("request_header["+header+"]", value)
	}

	req, err := io.ReadAll(c.Request.Body)
	if err != nil {
		c.AbortWithStatusJSON(http.StatusBadRequest, gin.H{
			"status": "error",
			"error":  client.Fail(span, err).Error(),
		})
		return
	}

	reqID := c.Request.Header.Get("X-Request-ID")
	if reqID == "" {
		reqID = uuid.NewString()
		c.Request.Header.Set("X-Request-ID", reqID)
	}
	span.SetTag("x-request-id", reqID)

	c.Request.Body = io.NopCloser(bytes.NewReader(req))
	span.LogKV("request_body", string(req))

	logPrefix := "net.DefaultController [" + reqID + "]"
	l, err := util.LogRequest(c, c.Request)
	if err != nil {
		t.log.With("err", err).Error("failed to log request")
		c.AbortWithStatusJSON(http.StatusOK, gin.H{
			"status": "error",
			"error":  client.Fail(span, err).Error(),
		})
		return
	}
	l.WithPrefix(logPrefix).Info("request")

	defer t.logResponse(reqID, c)

	if err = t.applyBalancer(c); err != nil {
		t.log.With("err", err).Error("failed to apply balancer")
		c.AbortWithStatusJSON(http.StatusOK, gin.H{
			"status": "error",
			"error":  client.Fail(span, err).Error(),
		})
		return
	}

	c.JSON(http.StatusOK, gin.H{
		"status": "success",
	})
}

func (t *DefaultController) logResponse(reqID string, c *gin.Context) {
	t.log.WithPrefix("net.DefaultController ["+reqID+"]").
		With("response_status", c.Writer.Status()).
		Info("response")
}

func (t *DefaultController) applyBalancer(c *gin.Context) error {
	return t.balancer.Apply(c)
}
