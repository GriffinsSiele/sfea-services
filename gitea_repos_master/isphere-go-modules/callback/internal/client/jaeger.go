package client

import (
	"context"
	"fmt"
	"io"
	"os"

	"github.com/charmbracelet/log"
	"github.com/gin-gonic/gin"
	"github.com/opentracing/opentracing-go"
	olog "github.com/opentracing/opentracing-go/log"
	"github.com/uber/jaeger-client-go"
	"github.com/uber/jaeger-client-go/config"
)

func NewTracer() (opentracing.Tracer, io.Closer, error) {
	cfg := config.Configuration{
		ServiceName: "callback",
		Sampler: &config.SamplerConfig{
			Type:  jaeger.SamplerTypeConst,
			Param: 1,
		},
		Reporter: &config.ReporterConfig{
			LogSpans:           true,
			LocalAgentHostPort: os.Getenv("JAEGER_AGENT_HOST_PORT"),
			QueueSize:          100,
		},
	}

	tracer, closer, err := cfg.NewTracer(config.Logger(jaeger.NullLogger))
	if err != nil {
		return nil, nil, fmt.Errorf("failed to create new tracer: %w", err)
	}

	return tracer, closer, nil
}

func StartSpanWithContext(ctx context.Context, tracer opentracing.Tracer, operationName string) (context.Context, opentracing.Span) {
	spanOptions := make([]opentracing.StartSpanOption, 0, 1)
	if parentSpanContext, ok := ctx.Value("parentSpan").(opentracing.Span); ok {
		spanOptions = append(spanOptions, opentracing.ChildOf(parentSpanContext.Context()))
	}

	span := tracer.StartSpan(operationName, spanOptions...)
	return context.WithValue(ctx, "parentSpan", span), span
}

func StartSpanWithGinContext(c *gin.Context, tracer opentracing.Tracer, operationName string) (*gin.Context, opentracing.Span) {
	ctx, span := StartSpanWithContext(c, tracer, operationName)
	c.Set("parentSpan", ctx.Value("parentSpan"))
	return c, span
}

func MustTracerCloser() (opentracing.Tracer, io.Closer) {
	tracer, closer, err := NewTracer()
	if err != nil {
		log.WithPrefix("client.jaeger").With("error", err).Fatal("failed to create new tracer")
	}
	return tracer, closer
}

func MustClose(closer io.Closer) {
	//goland:noinspection GoUnhandledErrorResult
	closer.Close()
}

func Fail(span opentracing.Span, err error) error {
	span.SetTag("error", true)
	span.LogFields(olog.String("error", err.Error()))
	return err
}
