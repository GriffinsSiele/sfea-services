package main

import (
	"context"
	"fmt"
	"io"

	"github.com/opentracing/opentracing-go"
	"github.com/uber/jaeger-client-go"
	"github.com/uber/jaeger-client-go/config"
)

func NewTracerCloser() (opentracing.Tracer, io.Closer, error) {
	cfg := config.Configuration{
		ServiceName: "geoip",
		Sampler: &config.SamplerConfig{
			Type:  "const",
			Param: 1,
		},
		Reporter: &config.ReporterConfig{
			LogSpans:           true,
			LocalAgentHostPort: jaegerEndpoint,
		},
	}

	tracer, closer, err := cfg.NewTracer(config.Logger(jaeger.NullLogger))
	if err != nil {
		return nil, nil, fmt.Errorf("failed to create new tracer: %w", err)
	}

	return tracer, closer, nil
}

func StartSpanWithContext(ctx context.Context, tracer opentracing.Tracer, operationName string) (context.Context, opentracing.Span) {
	spanOptions := make([]opentracing.StartSpanOption, 0)
	if parentSpanContext, ok := ctx.Value("parentSpanContext").(opentracing.SpanContext); ok {
		spanOptions = append(spanOptions, opentracing.ChildOf(parentSpanContext))
	}

	span := tracer.StartSpan(operationName, spanOptions...)
	ctx = context.WithValue(ctx, "parentSpanContext", span.Context())

	return ctx, span
}
