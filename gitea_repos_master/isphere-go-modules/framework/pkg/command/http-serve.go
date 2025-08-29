package command

import (
	"context"
	"encoding/json"
	"fmt"
	"github.com/sirupsen/logrus"
	"io"
	"math"
	"net/http"
	"strconv"
	"strings"
	"time"

	"git.i-sphere.ru/isphere-go-modules/framework/pkg/contract"
	"git.i-sphere.ru/isphere-go-modules/framework/pkg/decorator"
	"git.i-sphere.ru/isphere-go-modules/framework/pkg/model"
	"git.i-sphere.ru/isphere-go-modules/framework/pkg/validator"
	"github.com/gin-gonic/gin"
	"github.com/moogar0880/problems"
	"github.com/urfave/cli/v2"
)

type HTTPServe struct {
	messageFactory contract.MessageFactory
	processor      *decorator.Processor
	validator      *validator.Validator
}

func NewHTTPServe(messageFactory contract.MessageFactory, processor *decorator.Processor, validator *validator.Validator) *HTTPServe {
	return &HTTPServe{
		messageFactory: messageFactory,
		processor:      processor,
		validator:      validator,
	}
}

func (t *HTTPServe) Command() *cli.Command {
	return &cli.Command{
		Category: "http",
		Name:     "http:serve",
		Action:   t.action,
		Flags: cli.FlagsByName{
			&cli.StringFlag{
				Name:  "host",
				Value: model.DefaultHTTPServeHost,
			},
			&cli.IntFlag{
				Name:  "port",
				Value: model.DefaultHTTPServePort,
			},
		},
	}
}

func (t *HTTPServe) action(ctx *cli.Context) error {
	flags := &model.HTTPServeFlags{
		Host: ctx.String("host"),
		Port: ctx.Int("port"),
	}

	router := gin.New()
	router.POST("/", func(ctx *gin.Context) {
		if err := t.handler(ctx); err != nil {
			ctx.Header("Content-Type", problems.ProblemMediaType)

			problems.StatusProblemHandler(
				problems.NewDetailedProblem(ctx.Writer.Status(), err.Error()),
			).ServeHTTP(ctx.Writer, ctx.Request)
		}
	})

	if err := router.Run(flags.GetAddr()); err != nil {
		return fmt.Errorf("failed to run HTTP server: %w", err)
	}

	return nil
}

func (t *HTTPServe) handler(ctx *gin.Context) *gin.Error {
	defer func() {
		if err := ctx.Request.Body.Close(); err != nil {
			logrus.WithError(err).Errorf("cannot close request body: %v", err)
		}
	}()

	request, err := io.ReadAll(ctx.Request.Body)
	if err != nil {
		return ctx.AbortWithError(http.StatusInternalServerError, fmt.Errorf("failed to read request: %w", err))
	}

	var message = t.messageFactory.New()
	if err = json.Unmarshal(request, &message); err != nil {
		return ctx.AbortWithError(http.StatusBadRequest, fmt.Errorf("failed to unserialize message: %w", err))
	}

	if err = t.validator.Struct(message); err != nil {
		return ctx.AbortWithError(http.StatusUnprocessableEntity, fmt.Errorf("failed to validate message: %w", err))
	}

	var processorCtx = context.Context(ctx)

	if strings.Contains(ctx.GetHeader(contract.CacheControl), string(contract.CacheControlNoCache)) {
		ctx.Header("Vary", contract.CacheControl)

		processorCtx = context.WithValue(processorCtx, contract.CacheControlNoCache, new(any))
	}

	if strings.Contains(ctx.GetHeader(contract.CacheControl), string(contract.CacheControlNoStore)) {
		ctx.Header("Vary", contract.CacheControl)

		processorCtx = context.WithValue(processorCtx, contract.CacheControlNoStore, new(any))
	}

	if strings.Contains(ctx.GetHeader(contract.CacheControl), string(contract.CacheControlOnlyIfCached)) {
		ctx.Set("Vary", contract.CacheControl)

		processorCtx = context.WithValue(processorCtx, contract.CacheControlOnlyIfCached, new(any))
	}

	if contract.MatchCacheControlMaxAge.MatchString(ctx.GetHeader(contract.CacheControl)) {
		maxAgeString := contract.MatchCacheControlMaxAge.FindString(ctx.GetHeader(contract.CacheControl))

		maxAge, err := strconv.Atoi(maxAgeString)
		if err != nil {
			return ctx.AbortWithError(http.StatusBadRequest, fmt.Errorf("failed to cast max-age as int: %w", err))
		}

		ctx.Header("Vary", contract.CacheControl)

		processorCtx = context.WithValue(processorCtx, contract.CacheControlMaxAge, maxAge)
	}

	response, err := t.processor.Invoke(processorCtx, message)
	if err != nil {
		return ctx.AbortWithError(http.StatusInternalServerError, fmt.Errorf("failed to invoke processor: %w", err))
	}

	ctx.Header("Content-Type", "application/json")

	timestamp := time.Unix(response.Timestamp, 0)

	ctx.Header("Age", strconv.Itoa(int(math.Round(time.Since(timestamp).Seconds()))))
	ctx.Header("Last-Modified", timestamp.Format(time.RFC1123))

	if response.TTL != nil {
		ctx.Header("ETag", message.GetKey())
		ctx.Header("Expires", timestamp.Add(*response.TTL).Format(time.RFC1123))
	}

	ctx.JSON(http.StatusOK, response)

	return nil
}
