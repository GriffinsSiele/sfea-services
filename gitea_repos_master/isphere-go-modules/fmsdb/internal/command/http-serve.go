package command

import (
	"bytes"
	"fmt"
	"io"
	"net"
	"net/http"
	"strconv"
	"time"

	"git.i-sphere.ru/isphere-go-modules/fmsdb/internal/service"
	"github.com/gin-gonic/gin"
	"github.com/google/uuid"
	"github.com/longkai/rfc7807"
	"github.com/sirupsen/logrus"
	"github.com/urfave/cli/v2"
)

type HTTPServeCommand struct {
	passportRepository *service.PassportRepository
}

func NewHTTPServeCommand(passportRepository *service.PassportRepository) *HTTPServeCommand {
	return &HTTPServeCommand{
		passportRepository: passportRepository,
	}
}

func (t *HTTPServeCommand) Describe() *cli.Command {
	return &cli.Command{
		Category: "http",
		Name:     "http:serve",
		Action:   t.Action,
		Flags: cli.FlagsByName{
			&cli.StringFlag{
				Name:     "host",
				Required: true,
				EnvVars:  []string{"HTTP_HOST"},
			},
			&cli.IntFlag{
				Name:     "port",
				Required: true,
				EnvVars:  []string{"HTTP_PORT"},
			},
		},
	}
}

func (t *HTTPServeCommand) Action(c *cli.Context) error {
	if err := t.passportRepository.Init(c.Context); err != nil {
		logrus.WithError(err).Error("cannot initialize repository, all requests will be empty")
		logrus.Warn("needs for run synchronization command")
	}

	engine := gin.Default()
	engine.Use(requestID)
	engine.Use(logger)
	engine.GET("/passport", t.passportHandler)
	engine.POST("/_internal/synchronize", t.synchronizeHandler)

	addr := net.JoinHostPort(c.String("host"), strconv.Itoa(c.Int("port")))
	if err := engine.Run(addr); err != nil {
		return fmt.Errorf("run HTTP server: %w", err)
	}

	return nil
}

func (t *HTTPServeCommand) passportHandler(c *gin.Context) {
	if !t.passportRepository.Initialized() {
		problem := rfc7807.New(rfc7807.Aborted, "repository is not initialized")

		c.JSON(problem.Status, problem)

		return
	}

	var input Input

	if err := c.ShouldBindQuery(&input); err != nil {
		problem := rfc7807.New(rfc7807.InvalidArgument, err.Error())

		c.JSON(problem.Status, problem)

		return
	}

	out := &Output{
		Series: input.Series,
		Number: input.Number,
	}

	if t.passportRepository.Exists(c, input.Series, input.Number) {
		out.Status = OutputStatusNonValid
	} else {
		out.Status = OutputStatusValid
	}

	c.JSON(http.StatusOK, out)
}

func (t *HTTPServeCommand) synchronizeHandler(c *gin.Context) {
	if err := t.passportRepository.Init(c); err != nil {
		problem := rfc7807.New(rfc7807.Internal, err.Error())

		c.JSON(problem.Status, problem)

		return
	}

	c.JSON(http.StatusOK, gin.H{"type": "OK", "status": http.StatusOK})
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

// ---

type Input struct {
	Series uint16 `binding:"required" form:"series"`
	Number uint32 `binding:"required" form:"number"`
}

// ---

type Output struct {
	Series uint16       `json:"series"`
	Number uint32       `json:"number"`
	Status OutputStatus `json:"status"`
}

type OutputStatus string

const (
	OutputStatusValid    OutputStatus = "valid"
	OutputStatusNonValid OutputStatus = "non-valid"
)
