package tracing

import (
	"context"
	"fmt"
	"io"
	"os"

	"gitea-http.gitea.svc.cluster.local/isphere-go-modules/coordinator/internal/contract"
	"github.com/opentracing/opentracing-go"
	"github.com/sirupsen/logrus"
	"github.com/uber/jaeger-client-go"
	"github.com/uber/jaeger-client-go/config"
)

func NewTracer() (opentracing.Tracer, io.Closer, error) {
	cfg := config.Configuration{
		ServiceName: "coordinator",
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
	if parentSpanContext, ok := ctx.Value(contract.TracerParentCtxValue).(opentracing.Span); ok {
		spanOptions = append(spanOptions, opentracing.ChildOf(parentSpanContext.Context()))
	}

	span := tracer.StartSpan(operationName, spanOptions...)
	return context.WithValue(ctx, contract.TracerParentCtxValue, span), span
}

func MustTracerCloser() (opentracing.Tracer, io.Closer) {
	tracer, closer, err := NewTracer()
	if err != nil {
		logrus.WithError(err).Fatal("failed to create tracer")
	}
	return tracer, closer
}

func MustClose(closer io.Closer) {
	//goland:noinspection GoUnhandledErrorResult
	closer.Close()
}
