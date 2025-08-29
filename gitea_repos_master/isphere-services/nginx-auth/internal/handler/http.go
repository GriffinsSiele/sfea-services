package handler

import (
	"bytes"
	"errors"
	"fmt"
	"io"
	"log/slog"
	"net"
	"net/http"
	"net/url"
	"strconv"
	"sync"
	"sync/atomic"
	"time"

	"github.com/google/uuid"
	"i-sphere.ru/nginx-auth/internal/configuration"
	"i-sphere.ru/nginx-auth/internal/contract"
	"i-sphere.ru/nginx-auth/internal/tcp"
	"i-sphere.ru/nginx-auth/internal/x"
)

type HTTP struct {
	geoIP  *x.GeoIP
	params *configuration.Params
}

func NewHTTP(geoip2 *x.GeoIP, params *configuration.Params) *HTTP {
	return &HTTP{
		geoIP:  geoip2,
		params: params,
	}
}

func (h *HTTP) ServeHTTP(respWriter http.ResponseWriter, req *http.Request) {
	start := time.Now()
	var group sync.WaitGroup
	var clientHelloPtr atomic.Pointer[x.TLSPlaintext]
	var countryCodePtr atomic.Pointer[string]

	group.Add(1)
	go func() {
		defer group.Done()
		if clientHello, err := h.readClientHello(req); err != nil {
			slog.With("error", err).WarnContext(req.Context(), "failed to read JA3")
		} else {
			clientHelloPtr.Swap(clientHello)
		}
	}()

	group.Add(1)
	go func() {
		defer group.Done()
		if countryCode, err := h.geoIP.FindCountryCodeByRequest(req); err != nil {
			slog.With("error", err).WarnContext(req.Context(), "failed to detect country code")
		} else {
			countryCodePtr.Swap(&countryCode)
		}
	}()

	group.Wait()

	clientHello := clientHelloPtr.Load()
	countryCode := countryCodePtr.Load()

	respWriter = tcp.NewWithResponseWriter(respWriter)

	h.addBaseHeaders(respWriter.(*tcp.MemoryRecorder), req)

	if err := h.securityCheck(respWriter, req, clientHello, countryCode); err != nil {
		x.Problem(respWriter, req, err, http.StatusForbidden)
		return
	}

	if !h.internalRoute(req) {
		h.handleRequest(respWriter.(*tcp.MemoryRecorder), req)
	} else {
		MetadataHandler(respWriter, req, clientHello, countryCode)
	}

	h.logRequest(respWriter.(*tcp.MemoryRecorder), req, clientHello, &start, countryCode)
}

func (h *HTTP) securityCheck(respWriter http.ResponseWriter, req *http.Request, clientHello *x.TLSPlaintext, countryCode *string) error {
	// TODO main logic here

	return nil
}

func (h *HTTP) internalRoute(req *http.Request) bool {
	if req.RequestURI == "/.well-known/connection" && req.Method == http.MethodGet {
		return true
	}
	return false
}

func (h *HTTP) readClientHello(req *http.Request) (*x.TLSPlaintext, error) {
	netConn := req.Context().Value(contract.ConnContextKey)
	if netConn == nil {
		return nil, nil
	}

	buffConn, ok := netConn.(*tcp.BufferedConn)
	if !ok {
		return nil, fmt.Errorf("failed to cast connection to BufferedConn")
	}

	clientHello, err := h.parseClientHello(buffConn)
	if err != nil {
		return nil, fmt.Errorf("failed to parse TLS: %w", err)
	}

	return clientHello, nil
}

func (h *HTTP) parseClientHello(conn *tcp.BufferedConn) (*x.TLSPlaintext, error) {
	clientHello, err := x.NewTLSPlaintextWithReader(bytes.NewReader(conn.Bytes()))
	if err != nil {
		return nil, fmt.Errorf("failed to create TLSPlaintext: %w", err)
	}
	return clientHello, nil
}

func (h *HTTP) handleRequest(respWriter *tcp.MemoryRecorder, req *http.Request) {
	serverHost, serverPortStr, err := net.SplitHostPort(req.Host)
	if err != nil {
		serverHost = req.Host
		if req.TLS != nil {
			serverPortStr = "443"
		} else {
			serverPortStr = "80"
		}
	}

	serverPort, err := strconv.ParseUint(serverPortStr, 10, 64)
	if err != nil {
		x.Problem(respWriter, req, fmt.Errorf("failed to parse request port"), http.StatusBadRequest)
	}

	server := configuration.Server{
		Scheme: "http",
		Host:   serverHost,
		Port:   serverPort,
	}

	route, err := h.findRouteByHostAndPort(server.Host, server.Port)
	if err != nil {
		x.Problem(respWriter, req, fmt.Errorf("requested route %s is not provided", net.JoinHostPort(server.Host, serverPortStr)), http.StatusNotFound)
		return
	}

	switch {
	case route.Origin != nil:
		h.handleOrigin(respWriter, req, route.Origin)

	case route.Redirect != nil:
		h.handleRedirect(respWriter, req, route.Redirect)

	default:
		x.Problem(respWriter, req, errors.New("route misconfiguration detected"), http.StatusInternalServerError)
	}
}

func (h *HTTP) addBaseHeaders(respWriter *tcp.MemoryRecorder, req *http.Request) {
	requestID := req.Header.Get("X-Request-ID")
	if requestID == "" {
		requestID = uuid.NewString()
	}

	respWriter.Header().Add("Server", h.params.ServerName)
	respWriter.Header().Add("X-Request-ID", requestID)
}

func (h *HTTP) handleOrigin(respWriter *tcp.MemoryRecorder, req *http.Request, server *configuration.Server) {
	originURL, err := h.createURL(req, server)
	if err != nil {
		x.Problem(respWriter, req, fmt.Errorf("failed to create origin URL: %w", err), http.StatusInternalServerError)
		return
	}

	// convert request to reverse-proxy compatible state
	req.URL = originURL
	req.Host = originURL.Host
	req.RequestURI = ""
	req.TLS = nil

	resp, err := h.doRequest(req)
	if err != nil {
		x.Problem(respWriter, req, fmt.Errorf("failed to do request: %w", err), http.StatusBadGateway)
		return
	}
	//goland:noinspection GoUnhandledErrorResult
	defer resp.Body.Close()

	h.copyHeaders(respWriter, resp)
	h.writeResponse(respWriter, resp)
}

func (h *HTTP) handleRedirect(respWriter *tcp.MemoryRecorder, req *http.Request, server *configuration.Server) {
	redirectURL, err := h.createURL(req, server)
	if err != nil {
		x.Problem(respWriter, req, fmt.Errorf("failed to create redirect URL: %w", err), http.StatusInternalServerError)
		return
	}

	http.Redirect(respWriter, req, redirectURL.String(), http.StatusFound)
}

func (h *HTTP) createURL(req *http.Request, server *configuration.Server) (*url.URL, error) {
	originScheme := x.Coalesce(server.Scheme, h.getScheme(req))
	serverHost, serverPortStr := x.SplitHostPortSafe(req)

	serverPort, err := strconv.ParseUint(serverPortStr, 10, 64)
	if err != nil {
		return nil, fmt.Errorf("failed to parse cast port as uint: %w", err)
	}

	originHost, originPort := x.Coalesce(server.Host, serverHost), x.Coalesce(server.Port, serverPort)

	originURL, err := url.ParseRequestURI(req.RequestURI)
	if err != nil {
		return nil, fmt.Errorf("failed to self parse request uri: %w", err)
	}

	originURL.Scheme = originScheme
	originURL.Host = net.JoinHostPort(originHost, strconv.FormatUint(originPort, 10))

	return originURL, nil
}

func (h *HTTP) getScheme(req *http.Request) string {
	if req.TLS != nil {
		return "https"
	}
	return "http"
}

func (h *HTTP) doRequest(req *http.Request) (*http.Response, error) {
	resp, err := http.DefaultClient.Do(req)
	if err != nil {
		return nil, fmt.Errorf("failed to do request throught default HTTP client: %w", err)
	}

	return resp, nil
}

func (h *HTTP) copyHeaders(respWriter http.ResponseWriter, resp *http.Response) {
	for headerKey, headerValues := range resp.Header {
		for _, headerValue := range headerValues {
			respWriter.Header().Add(headerKey, headerValue)
		}
	}
}

func (h *HTTP) writeResponse(respWriter http.ResponseWriter, resp *http.Response) {
	respWriter.WriteHeader(resp.StatusCode)
	if _, err := io.Copy(respWriter, resp.Body); err != nil {
		x.Problem(respWriter, resp.Request, fmt.Errorf("failed to proxy response: %w", err), http.StatusBadGateway)
	}
}

func (h *HTTP) logRequest(respWriter *tcp.MemoryRecorder, req *http.Request, clientHello *x.TLSPlaintext, start *time.Time, countryCode *string) {
	logCtx := x.NewLogContext(respWriter, req, clientHello, start, countryCode)

	switch {
	case // informational or redirect
		respWriter.StatusCode >= 100 && respWriter.StatusCode < 200,
		respWriter.StatusCode >= 300 && respWriter.StatusCode < 400:
		slog.DebugContext(req.Context(), logCtx.String())
	case // success
		respWriter.StatusCode >= 200 && respWriter.StatusCode < 300:
		slog.InfoContext(req.Context(), logCtx.String())
	case // client error
		respWriter.StatusCode >= 400 && respWriter.StatusCode < 500:
		slog.WarnContext(req.Context(), logCtx.String())
	default: // server error or other
		slog.ErrorContext(req.Context(), logCtx.String())
	}
}

func (h *HTTP) findRouteByHostAndPort(host string, port uint64) (*configuration.Route, error) {
	for _, upstream := range h.params.Upstreams {
		for _, route := range upstream.Routes {
			if route.Server.Host == host && route.Server.Port == port {
				return route, nil
			}
		}
	}

	return nil, fmt.Errorf("failed to find route by host and port: %s:%d", host, port)
}
