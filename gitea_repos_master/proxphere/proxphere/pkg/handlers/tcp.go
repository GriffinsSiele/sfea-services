package handlers

import (
	"bufio"
	"bytes"
	"context"
	"errors"
	"fmt"
	"io"
	"net"
	"net/url"
	"strings"
	"time"

	"github.com/Danny-Dasilva/CycleTLS/cycletls"
	http "github.com/Danny-Dasilva/fhttp"
	"github.com/Danny-Dasilva/fhttp/httputil"
	"github.com/charmbracelet/log"
	"github.com/go-redis/redis/v8"
	"go.i-sphere.ru/proxy/pkg/adapters"
	"go.i-sphere.ru/proxy/pkg/clients"
	"go.i-sphere.ru/proxy/pkg/contracts"
	"go.i-sphere.ru/proxy/pkg/managers"
	"go.i-sphere.ru/proxy/pkg/models"
	"go.i-sphere.ru/proxy/pkg/trackers"
	"go.i-sphere.ru/proxy/pkg/utils"
)

type TCP struct {
	hasura           *clients.Hasura
	proxySpecAdapter *adapters.ProxySpec
	proxySpecManager *managers.ProxySpec
	redis            *redis.Client

	log *log.Logger
}

func NewTCP(
	hasura *clients.Hasura,
	proxySpecAdapter *adapters.ProxySpec,
	proxySpecManager *managers.ProxySpec,
	redis *redis.Client,
) *TCP {
	return &TCP{
		hasura:           hasura,
		proxySpecAdapter: proxySpecAdapter,
		proxySpecManager: proxySpecManager,
		redis:            redis,

		log: log.WithPrefix("handlers.TCP"),
	}
}

func (t *TCP) HandleWithScheme(ctx context.Context, conn net.Conn, scheme string) {
	tracer, closer := trackers.MustTracerCloser()
	defer trackers.MustClose(closer)

	ctx, span := trackers.StartSpanWithContext(ctx, tracer, "handle tcp request")
	defer span.Finish()

	span.SetTag("scope", "tcp")
	span.LogKV("scheme", scheme)

	logger := t.log.With("local_addr", conn.LocalAddr(), "remote_addr", conn.RemoteAddr())
	logger.Debug("connection opened")

	defer func() {
		logger.Debug("connection closed")
		//goland:noinspection GoUnhandledErrorResult
		conn.Close()
	}()

	if err := t.handleWithScheme(ctx, conn, scheme); err != nil {
		logger.With("error", err).Error("unexpected handle error")
		//goland:noinspection GoUnhandledErrorResult
		t.handleError(conn, trackers.Fail(span, err))
	}
}

func (t *TCP) handleWithScheme(ctx context.Context, conn net.Conn, scheme string) error {
	tracer, closer := trackers.MustTracerCloser()
	defer trackers.MustClose(closer)

	ctx, span := trackers.StartSpanWithContext(ctx, tracer, "process request")
	defer span.Finish()

	req, cancel, err := t.parseRequestWithScheme(ctx, conn, scheme)
	if cancel != nil {
		defer cancel()
	}
	if err != nil {
		return trackers.Fail(span, fmt.Errorf("failed to parse request: %w", err))
	}
	reqBytes, err := httputil.DumpRequest(req, true)
	if err != nil {
		return trackers.Fail(span, fmt.Errorf("failed to dump request: %w", err))
	}
	span.LogKV("request", string(reqBytes))

	proxySpecs, err := t.proxySpecAdapter.SelectThroughStrategiesWithRequest(req.Context(), req)
	if err != nil {
		return trackers.Fail(span, fmt.Errorf("failed to select proxy spec: %w", err))
	}
	if len(proxySpecs) == 0 {
		proxySpecs = append(proxySpecs, t.proxySpecAdapter.Default())
	}
	span.LogKV("proxy_specs_count", len(proxySpecs))

	requestBytes, err := io.ReadAll(req.Body)
	if err != nil {
		return trackers.Fail(span, fmt.Errorf("failed to read request body: %w", err))
	}

	var attemptsCount int
	var lastErr error
l:
	for _, proxySpec := range proxySpecs {
		select {
		case <-ctx.Done():
			break l
		default:
			break
		}

		// if attemptsCount > N {}
		attemptsCount++

		if ja3 := utils.RequestParam(req, contracts.XSphereJA3); ja3 != "" {
			proxySpec.JA3 = &ja3
		}

		if err = t.attempt(req, proxySpec, conn, requestBytes); err != nil {
			lastErr = err
			continue
		}

		return nil
	}

	return trackers.Fail(span, utils.Coalesce(lastErr, errors.New("all attempts failed")))
}

func (t *TCP) attempt(req *http.Request, proxySpec *models.ProxySpec, conn net.Conn, requestBytes []byte) error {
	tracer, closer := trackers.MustTracerCloser()
	defer trackers.MustClose(closer)

	ctx, span := trackers.StartSpanWithContext(req.Context(), tracer, "trying to connect")
	defer span.Finish()

	cancelCtx, cancel, err := t.createContextForRequest(req)
	if err != nil {
		return trackers.Fail(span, fmt.Errorf("failed to create context: %w", err))
	}
	defer cancel()

	req = req.WithContext(cancelCtx)
	req = utils.CleanRequest(req)

	logOptions := managers.NewProxySpecLogOptions().
		WithContext(req.Context()).
		WithRequest(req).
		WithProxySpec(proxySpec)

	defer func(logOptions *managers.ProxySpecLogOptions) {
		//goland:noinspection GoUnhandledErrorResult
		t.proxySpecManager.LogRequestResponse(logOptions)
	}(logOptions)

	var options cycletls.Options

	if proxySpec != nil {
		if ja3 := proxySpec.JA3; ja3 != nil {
			logOptions.WithJA3(ja3)
			options.Ja3 = *ja3
		}
		if u := proxySpec.URL; u != nil {
			options.Proxy = u.String()
		}
	}

	options.Headers = make(map[string]string)
	for k, vv := range req.Header {
		options.Headers[k] = strings.Join(vv, ",")
	}
	options.UserAgent = req.UserAgent()
	options.Body = string(requestBytes)
	options.URL = req.URL.String()
	options.Method = req.Method
	options.InsecureSkipVerify = true

	client := cycletls.Init()
	resp, err := client.Do(req.URL.String(), options, req.Method)
	if err != nil {
		logOptions.WithError(err)
		return trackers.Fail(span, fmt.Errorf("failed to perform request: %w", err))
	}

	var httpResp http.Response
	httpResp.Proto, httpResp.ProtoMajor, httpResp.ProtoMinor = req.Proto, req.ProtoMajor, req.ProtoMinor
	httpResp.StatusCode = resp.Status
	httpResp.Status = http.StatusText(resp.Status)
	httpResp.Header = http.Header{}
	for k, v := range resp.Headers {
		if k == "Content-Encoding" && strings.Contains(v, "gzip") {
			continue // code below already unzip response
		}
		httpResp.Header.Add(k, v)
	}
	httpResp.Body = io.NopCloser(strings.NewReader(resp.Body))
	logOptions.WithResponse(&httpResp)

	t.addSpecialHeaders(&httpResp, logOptions)

	if err = t.sendResponse(ctx, conn, &httpResp); err != nil {
		return trackers.Fail(span, fmt.Errorf("failed to send response: %w", err))
	}
	return nil
}

func (t *TCP) createContextForRequest(req *http.Request) (context.Context, context.CancelFunc, error) {
	if ttlStr := utils.RequestParam(req, contracts.XSphereProxySpecStrategyTTL); ttlStr != "" {
		ttl, err := time.ParseDuration(ttlStr)
		if err != nil {
			return nil, nil, fmt.Errorf("failed to parse XSphereProxySpecStrategyTTL: %w", err)
		}
		ctx, cancel := context.WithTimeout(req.Context(), ttl)
		return ctx, cancel, nil
	}
	ctx, cancel := context.WithCancel(req.Context())
	return ctx, cancel, nil
}

func (t *TCP) sendResponse(ctx context.Context, conn net.Conn, resp *http.Response) error {
	tracer, closer := trackers.MustTracerCloser()
	defer trackers.MustClose(closer)

	ctx, span := trackers.StartSpanWithContext(ctx, tracer, "send response to client")
	defer span.Finish()

	resp, err := utils.UnpackResponse(resp)
	if err != nil {
		return trackers.Fail(span, fmt.Errorf("failed to unpack response: %w", err))
	}
	b, err := httputil.DumpResponse(resp, true)
	if err != nil {
		return trackers.Fail(span, fmt.Errorf("failed to dump response: %w", err))
	}
	span.LogKV("response", string(b))
	if _, err = io.Copy(conn, bytes.NewReader(b)); err != nil {
		return trackers.Fail(span, fmt.Errorf("failed to write response: %w", err))
	}
	return nil
}

func (t *TCP) parseRequestWithScheme(ctx context.Context, conn net.Conn, scheme string) (*http.Request, context.CancelFunc, error) {
	tracer, closer := trackers.MustTracerCloser()
	defer trackers.MustClose(closer)

	ctx, span := trackers.StartSpanWithContext(ctx, tracer, "parse incoming request")
	defer span.Finish()

	req, err := http.ReadRequest(bufio.NewReader(conn))
	if err != nil {
		return nil, nil, trackers.Fail(span, fmt.Errorf("failed to read request: %w", err))
	}

	reqURL, err := url.Parse(req.RequestURI)
	if err != nil {
		return nil, nil, trackers.Fail(span, fmt.Errorf("failed to parse request URL: %w", err))
	}

	t.mergeRequestWithSchemeURL(req, scheme, reqURL)

	usernamePassword, err := t.redis.HGet(ctx, "proxphere", conn.RemoteAddr().String()).Result()
	if err == nil {
		if username, password, ok := strings.Cut(usernamePassword, ":"); ok {
			if err = t.hasura.InjectHeadersByUsernameAndPassword(ctx, req, username, password); err != nil {
				return nil, nil, trackers.Fail(span, fmt.Errorf("failed to inject headers: %w", err))
			}
		}
	}

	var cancel context.CancelFunc
	if ttlStr := utils.RequestParam(req, contracts.XSphereProxySpecTTL); ttlStr != "" {
		ttl, err := time.ParseDuration(ttlStr)
		if err != nil {
			return nil, nil, trackers.Fail(span, fmt.Errorf("failed to parse XSphereProxySpecStrategyTTL: %w", err))
		}
		ctx, cancel = context.WithTimeout(req.Context(), ttl)
		req = req.WithContext(ctx)
	}

	requestHost := utils.Coalesce(req.Host, req.URL.Host)

	reqCtx := context.WithValue(ctx, "request_host", requestHost)
	span.LogKV("request_host", requestHost)

	return req.Clone(reqCtx), cancel, nil
}

func (t *TCP) mergeRequestWithSchemeURL(req *http.Request, scheme string, reqURL *url.URL) {
	reqURL.Scheme = scheme
	reqURL.Host = req.Host
	req.Host = ""
	req.RequestURI = ""
	req.URL = reqURL
}

func (t *TCP) addSpecialHeaders(resp *http.Response, logOptions *managers.ProxySpecLogOptions) {
	if s := logOptions.ProxySpec; s != nil {
		if int(s.ID) > 0 {
			t.withCookie(resp, string(contracts.XSphereProxySpecID), s.ID.String())
			resp.Header.Add(string(contracts.XSphereProxySpecID), s.ID.String())
		}
		if s.CountryCode != "" {
			resp.Header.Add(string(contracts.XSphereProxySpecCountryCode), s.CountryCode)
		}
	}
	if ja3 := logOptions.JA3; ja3 != nil {
		if *ja3 != "" {
			t.withCookie(resp, string(contracts.XSphereJA3), *ja3)
			resp.Header.Add(string(contracts.XSphereJA3), *ja3)
		}
	}
}

func (t *TCP) withCookie(resp *http.Response, name string, value string) {
	cookie := &http.Cookie{
		Name:     name,
		Value:    value,
		Path:     "/",
		HttpOnly: true,
		MaxAge:   int((24 * time.Hour * 7 /* days */).Seconds()), // week
	}
	resp.Header.Add("Set-Cookie", cookie.String())
}

func (t *TCP) handleError(conn net.Conn, wrapErr error) error {
	buf := bytes.NewBuffer(nil)
	//goland:noinspection GoUnhandledErrorResult
	fmt.Fprintln(buf, wrapErr.Error())

	resp := &http.Response{
		Proto:      "HTTP/1.1",
		ProtoMajor: 1,
		ProtoMinor: 1,
		StatusCode: http.StatusInternalServerError,
		Status:     http.StatusText(http.StatusInternalServerError),
		Header:     http.Header{"Content-Type": {"text/plain"}},
		Body:       io.NopCloser(buf),
	}

	respBytes, err := httputil.DumpResponse(resp, true)
	if err != nil {
		return fmt.Errorf("failed to dump error response: %w", err)
	}

	_, err = conn.Write(respBytes)
	return fmt.Errorf("failed to write error response: %w", err)
}
