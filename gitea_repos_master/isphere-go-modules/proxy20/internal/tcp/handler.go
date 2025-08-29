package tcp

import (
	"bufio"
	"bytes"
	"context"
	"crypto/tls"
	"errors"
	"fmt"
	"io"
	"math/rand"
	"net"
	"net/http"
	"net/http/httputil"
	"strconv"
	"strings"
	"time"

	"github.com/charmbracelet/log"
	"github.com/google/uuid"
	"github.com/sirupsen/logrus"
	"go.opentelemetry.io/otel/attribute"
	"go.opentelemetry.io/otel/trace"
	"golang.org/x/net/proxy"

	"i-sphere.ru/proxy/internal/connection"
	"i-sphere.ru/proxy/internal/model"
	"i-sphere.ru/proxy/internal/repository"
	"i-sphere.ru/proxy/internal/util"
)

type Handler struct {
	postgres  *connection.Postgres
	proxyRepo *repository.Proxy
	log       *log.Logger
	tracer    trace.Tracer
}

func NewHandler(postgres *connection.Postgres, proxyRepo *repository.Proxy, tracer trace.Tracer) *Handler {
	return &Handler{
		postgres:  postgres,
		proxyRepo: proxyRepo,
		log:       log.WithPrefix("tcp.Handler"),
		tracer:    tracer,
	}
}

func (t *Handler) HandleWithProto(ctx context.Context, conn net.Conn, proto string) error {
	start := time.Now()
	reqCtx := util.NewContext(ctx)

	// Parse HTTP request
	req, err := t.parseHTTPRequest(reqCtx, conn, proto)
	if err != nil {
		return fmt.Errorf("failed to parse HTTP request: %w", err)
	}
	req = req.WithContext(ctx)

	if reqCtx.RequestID == "" {
		reqCtx.RequestID = uuid.NewString()
	}

	if reqCtx.UberTraceID != "" {
		// TODO: use trace.FromContext
	}
	ctx, span := t.tracer.Start(ctx, "handle TCP conn")
	defer span.End()

	span.SetAttributes(attribute.String("request_id", reqCtx.RequestID))

	logrus.WithContext(ctx).WithFields(logrus.Fields{"method": req.Method, "url": req.URL.String()}).Debug("HTTP request")

	for i := 0; i < 3; i++ {
		wait := make(chan any)
		var cont bool
		go func() {
			defer close(wait)
			childCtx, childSpan := t.tracer.Start(ctx, "internal handle with proto")
			childCtx, cancel := context.WithTimeout(childCtx, 5*time.Second)
			defer cancel()
			childReq := req.WithContext(childCtx)
			logrus.WithContext(childCtx).WithField("attempt", i+1).Debug("attempting internal handle with proto")
			if err := t.handleWithProto(childCtx, conn, proto, childReq, reqCtx, span, start); err != nil {
				logrus.WithContext(childCtx).Warnf("failed to internal handle with proto: %v", err)
				childSpan.End()
				cont = true
			}
			childSpan.End()
		}()
		<-wait
		if cont {
			continue
		}
		return nil
	}

	err = errors.New("all attempts was failed to handle")
	logrus.WithContext(ctx).WithError(err).Error(err.Error())
	return err
}

func (t *Handler) handleWithProto(ctx context.Context, conn net.Conn, proto string, req *http.Request, reqCtx *util.Context, span trace.Span, start time.Time) error {
	var err error

	// Select proxy spec
	var proxySpec *model.ProxySpec
	if reqCtx.ProxyID > 0 {
		if proxySpec, err = t.proxyRepo.Find(ctx, reqCtx.ProxyID); err != nil {
			logrus.WithContext(ctx).WithError(err).Error("failed to find proxy spec")
			return fmt.Errorf("failed to find proxy spec: %w", err)
		}
	} else {
		if proxySpec, err = t.selectProxySpec(ctx, reqCtx); err != nil {
			logrus.WithContext(ctx).WithError(err).Error("failed to select proxy spec")
			return fmt.Errorf("failed to select proxy spec: %w", err)
		}
	}

	logrus.WithContext(ctx).WithFields(logrus.Fields{
		"id":         proxySpec.ID,
		"host":       proxySpec.Host,
		"port":       proxySpec.Port,
		"proxyGroup": proxySpec.ProxyGroup,
		"regionCode": proxySpec.RegionCode,
	}).Debug("selected proxy spec")

	span.SetAttributes(attribute.Int("proxy_id", proxySpec.ID))

	// Create proxy dialer
	dialer, err := NewHTTPDialer(proxySpec.URL(), proxy.Direct, req.URL, t.tracer, func(_ context.Context) error {
		// Log HTTP request
		reqBytes, err := httputil.DumpRequestOut(req, false)
		if err != nil {
			return fmt.Errorf("failed to dump request: %w", err)
		}

		logrus.WithContext(ctx).WithField("request", string(reqBytes)).Debug("HTTP request logged")

		return nil
	})
	if err != nil {
		logrus.WithContext(ctx).WithError(err).Error("failed to create proxy dialer")
		return fmt.Errorf("failed to create proxy dialer: %w", err)
	}

	// Do HTTP request
	transport := &http.Transport{
		DialContext: dialer.DialContext,
		TLSClientConfig: &tls.Config{
			InsecureSkipVerify: true,
		},
	}

	client := &http.Client{
		Transport: transport,
	}

	resp, err := client.Do(req)
	go t.logRequest(ctx, start, req, resp, proxySpec, err)
	if err != nil {
		logrus.WithContext(ctx).WithError(err).Error("failed to do request")
		return fmt.Errorf("failed to do request: %w", err)
	}

	//goland:noinspection GoUnhandledErrorResult
	defer resp.Body.Close()

	for headerKey := range reqCtx.SpecialHeaders {
		resp.Header.Add("Vary", headerKey)
	}
	resp.Header.Add("X-Sphere-Proxy-ID", strconv.Itoa(proxySpec.ID))
	resp.Header.Add("X-Request-ID", reqCtx.RequestID)
	resp.Header.Add("X-Sphere-Svc", fmt.Sprintf(`hx="%s";`, proto))

	// Write HTTP response
	respBytes, err := httputil.DumpResponse(resp, true)
	if err != nil {
		logrus.WithContext(ctx).WithError(err).Error("failed to dump response")
		return fmt.Errorf("failed to dump response: %w", err)
	}
	if _, err = conn.Write(respBytes); err != nil {
		logrus.WithContext(ctx).WithError(err).Error("failed to write response")
		return fmt.Errorf("failed to write response: %w", err)
	}

	logrus.WithContext(ctx).WithFields(logrus.Fields{
		"response": string(respBytes),
		"duration": time.Since(start),
	}).Debug("HTTP response")
	return nil
}

func (t *Handler) parseHTTPRequest(ctx *util.Context, conn net.Conn, originalProto string) (*http.Request, error) {
	reader := bufio.NewReader(conn)

	// Read first line
	firstLine, err := reader.ReadBytes('\n')
	if err != nil {
		return nil, fmt.Errorf("failed to read first line: %w", err)
	}

	method, rest, ok := bytes.Cut(bytes.TrimSpace(firstLine), []byte(" "))
	if !ok {
		return nil, errors.New("failed to parse first line method")
	}

	requestURI, _, ok := bytes.Cut(bytes.TrimSpace(rest), []byte(" "))
	if !ok {
		return nil, errors.New("failed to parse first line requestURI")
	}

	headers := http.Header{}

	// Read headers
	for {
		line, err := reader.ReadString('\n')
		if err != nil {
			return nil, fmt.Errorf("failed to read header line: %w", err)
		}

		if line == string([]byte{0x0d, 0x0a}) {
			break
		}

		headerKey, headerValue, ok := strings.Cut(strings.TrimSpace(line), ":")
		if !ok {
			return nil, fmt.Errorf("failed to parse header line: %s", line)
		}

		lowerHeaderKey := strings.ToLower(headerKey)
		headerValue = strings.TrimSpace(headerValue)
		if strings.HasPrefix(lowerHeaderKey, strings.ToLower("X-Sphere-")) {
			ctx.SpecialHeaders.Add(headerKey, headerValue)

			switch lowerHeaderKey {
			case strings.ToLower("X-Sphere-Proxy-ID"):
				if ctx.ProxyID, err = strconv.Atoi(headerValue); err != nil {
					return nil, fmt.Errorf("failed to cast proxy id as int: %s: %w", headerValue, err)
				}
				logrus.WithContext(ctx).WithField("proxy_id", ctx.ProxyID).Debug("use client proxy_id")
			case strings.ToLower("X-Sphere-ProxyGroup"):
				if ctx.ProxyGroup, err = strconv.Atoi(headerValue); err != nil {
					return nil, fmt.Errorf("failed to cast proxygroup as int: %s: %w", headerValue, err)
				}
				logrus.WithContext(ctx).WithField("proxy_group", ctx.ProxyGroup).Debug("use client proxy_group")
			case strings.ToLower("X-Sphere-Country"),
				strings.ToLower("X-Sphere-Region"):
				ctx.RegionCode = headerValue
				logrus.WithContext(ctx).WithField("country", ctx.RegionCode).Debug("use client country")
			}
			continue
		} else if lowerHeaderKey == strings.ToLower("X-Request-ID") {
			ctx.SpecialHeaders.Add(headerKey, headerValue)
			ctx.RequestID = headerValue
			continue
		} else if lowerHeaderKey == strings.ToLower("Uber-Trace-ID") {
			ctx.SpecialHeaders.Add(headerKey, headerValue)
			ctx.UberTraceID = headerValue
			continue
		}

		headers.Add(headerKey, headerValue)
	}

	var content io.Reader = http.NoBody

	contentLengthStr := headers.Get("Content-Length")

	if contentLengthStr != "" {
		contentLength, err := strconv.Atoi(contentLengthStr)
		if err != nil {
			return nil, fmt.Errorf("failed to parse content-length: %w", err)
		}

		contentBytes := make([]byte, contentLength)
		if _, err = reader.Read(contentBytes); err != nil {
			return nil, fmt.Errorf("failed to read content: %w", err)
		}

		content = bytes.NewReader(contentBytes)
	}

	req, err := http.NewRequestWithContext(ctx, string(method), string(requestURI), content)
	if err != nil {
		return nil, fmt.Errorf("failed to create request: %w", err)
	}

	for headerKey, headerValues := range headers {
		for _, headerValue := range headerValues {
			req.Header.Add(headerKey, headerValue)
		}
	}

	req.URL.Scheme = originalProto
	req.URL.Host = req.Header.Get("Host")
	ctx.Host = req.Header.Get("Host")
	req.Header.Del("Host")

	return req, nil
}

func (t *Handler) selectProxySpec(ctx context.Context, reqCtx *util.Context) (*model.ProxySpec, error) {
	proxySpecs, err := t.proxyRepo.FindByRequestContext(reqCtx, true)
	if err != nil {
		// util.FinishSpanWithError(span, err)
		return nil, fmt.Errorf("failed to find proxies n: %w", err)
	}

	if len(proxySpecs) == 0 {
		err := errors.New("no proxies found")
		// util.FinishSpanWithError(span, err)
		return nil, err
	}

	// Select random proxy
	proxySpec := proxySpecs[rand.Intn(len(proxySpecs))]

	return proxySpec, nil
}

func (t *Handler) logRequest(ctx context.Context, start time.Time, req *http.Request, resp *http.Response, proxySpec *model.ProxySpec, err error) {
	conn, err := t.postgres.Acquire(ctx)
	if err != nil {
		logrus.WithContext(ctx).WithError(err).Error("failed to acquire postgres connection")
		return
	}

	defer conn.Release()

	var statusCode int
	if resp != nil {
		statusCode = resp.StatusCode
	}

	var message *string
	if err != nil {
		errMsg := fmt.Sprintf("%v", err)
		message = &errMsg
	}

	// language=postgresql
	sql := `insert into proxy_specs_logs (proxy_spec_id, host, status_code, duration, message, created_at)
values ($1, $2, $3, $4, $5, $6)`
	if _, err = conn.Exec(ctx, sql, proxySpec.ID, req.URL.Hostname(), statusCode, time.Since(start), message, start); err != nil {
		logrus.WithContext(ctx).WithError(err).Error("failed to insert proxy spec log")
	}
}
