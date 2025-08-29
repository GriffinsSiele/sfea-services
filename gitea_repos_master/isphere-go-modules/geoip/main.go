package main

import (
	"bytes"
	"context"
	"encoding/binary"
	"encoding/json"
	"errors"
	"flag"
	"fmt"
	"net"
	"os"
	"runtime"
	"strings"
	"time"

	"github.com/getsentry/sentry-go"
	"github.com/google/uuid"
	"github.com/opentracing/opentracing-go/log"
	"github.com/oschwald/geoip2-golang"
	"github.com/sirupsen/logrus"
	"github.com/uber/jaeger-client-go"
	"github.com/valyala/fasthttp"
	"golang.org/x/sync/errgroup"
	"k8s.io/klog/v2"
)

const DefaultAddr = ":80"
const DefaultTCPAddr = ":9000"
const DefaultGeoliteFilename = "/var/lib/GeoLite2/GeoLite2-City.mmdb"
const DefaultJaegerEndpoint = "172.16.97.10:6831"

var addr string
var tcpAddr string
var geoLite2Filename string
var jaegerEndpoint string

func init() {
	addr = os.Getenv("ADDR")
	if addr == "" {
		addr = DefaultAddr
	}

	tcpAddr = os.Getenv("TCP_ADDR")
	if tcpAddr == "" {
		tcpAddr = DefaultTCPAddr
	}

	//goland:noinspection GoUnhandledErrorResult
	sentry.Init(sentry.ClientOptions{
		Dsn: os.Getenv("SENTRY_DSN"),
	})

	logrus.SetLevel(logrus.DebugLevel)
}

func main() {
	flag.StringVar(&geoLite2Filename, "f", DefaultGeoliteFilename, "geolite2-filename")
	flag.StringVar(&jaegerEndpoint, "jaeger-endpoint", DefaultJaegerEndpoint, "jaeger-endpoint")
	flag.Parse()

	db, err := geoip2.Open(geoLite2Filename)
	if err != nil {
		klog.Fatalf("failed to open geolite2 database: %v", err)
	}

	//goland:noinspection GoUnhandledErrorResult
	defer db.Close()

	errCh := make(chan error)
	go func() {
		klog.InfoS("listen HTTP server", "address", addr, "geolite2-filename", geoLite2Filename)
		if err = fasthttp.ListenAndServe(addr, func(ctx *fasthttp.RequestCtx) {
			handler(db, ctx)
		}); err != nil {
			errCh <- fmt.Errorf("failed to listen HTTP server: %v", err)
		}
	}()

	go func() {
		klog.InfoS("listen TCP server", "address", tcpAddr, "geolite2-filename", geoLite2Filename)

		listener, err := net.Listen("tcp", tcpAddr)
		if err != nil {
			errCh <- fmt.Errorf("failed to listen TCP server: %v", err)
		}
		//goland:noinspection GoUnhandledErrorResult
		defer listener.Close()

		for {
			conn, err := listener.Accept()
			if err != nil {
				klog.ErrorS(err, "failed to accept TCP connection")
				continue
			}

			go func() {
				//goland:noinspection GoUnhandledErrorResult
				defer conn.Close()
				tcpHandler(db, conn)
			}()
		}
	}()

	for err := range errCh {
		klog.Fatalf(fmt.Errorf("server fail: %w", err).Error())
	}
}

func tcpHandler(db *geoip2.Reader, conn net.Conn) {
	var ipv4Bytes [4]uint8
	if _, err := conn.Read(ipv4Bytes[:]); err != nil {
		klog.ErrorS(err, "failed to read ipv4")
		return
	}

	ipv4 := net.IP(ipv4Bytes[:])
	result, err := invoke(db, ipv4)
	if err != nil {
		klog.ErrorS(err, "failed to invoke")
	}

	resultCodeLen := len(result.CountryCode)
	var resultCodeLenBytes [2]byte
	binary.BigEndian.PutUint16(resultCodeLenBytes[:], uint16(resultCodeLen))

	if _, err := conn.Write(resultCodeLenBytes[:]); err != nil {
		klog.ErrorS(err, "failed to write country code len")
		return
	}

	if _, err := conn.Write([]byte(result.CountryCode)); err != nil {
		klog.ErrorS(err, "failed to write country code")
	}
}

func handler(db *geoip2.Reader, c *fasthttp.RequestCtx) {
	tracer, closer, _ := NewTracerCloser()
	defer closer.Close()

	var ctx context.Context = c
	if traceID := c.Request.Header.Peek("Uber-Trace-Id"); !bytes.Equal(traceID, nil) {
		spanContext, err := jaeger.ContextFromString(string(traceID))
		if err != nil {
			klog.ErrorS(err, "failed to decode x-request-id")
		} else {
			ctx = context.WithValue(ctx, "parentSpanContext", spanContext)
		}
	}

	ctx, span := StartSpanWithContext(ctx, tracer, "handle http request")
	defer span.Finish()

	span.LogFields(log.String("request", string(c.Request.Body())),
		log.String("query_args", c.QueryArgs().String()))
	defer func() {
		span.LogFields(log.String("response", string(c.Response.Body())))
	}()

	defer logRequest(c)
	setRequestIDIfEmpty(c)

	ips := make([]string, 0)

	switch {
	case c.QueryArgs().Has("ip"):
		args := c.QueryArgs().PeekMulti("ip")
		ips = make([]string, len(args))
		for i, arg := range args {
			ips[i] = string(arg)
		}
	default:
		if err := json.Unmarshal(c.Request.Body(), &ips); err != nil {
			span.SetTag("error", true)
			span.LogFields(log.String("error", err.Error()))
			fail(c, fasthttp.StatusBadRequest, err)
		}
	}

	var group errgroup.Group

	group.SetLimit(runtime.GOMAXPROCS(runtime.NumCPU()))

	results := make([]any, len(ips))

	for i, ip := range ips {
		i, ip := i, ip

		group.Go(func() error {
			_, childSpan := StartSpanWithContext(ctx, tracer, "invoke IP")
			defer childSpan.Finish()

			ipAddr := net.ParseIP(ip)
			if ipAddr == nil {
				err := fmt.Errorf("unprocessable ip addr: %s", ip)
				results[i] = &Result{
					IP:     ipAddr,
					Status: ResultStatusError,
					Error:  err.Error(),
				}
				childSpan.SetTag("error", true)
				childSpan.LogFields(log.String("error", err.Error()))
				return nil
			}

			childSpan.LogFields(log.String("ip", ipAddr.String()))

			result, err := invoke(db, ipAddr)
			if err != nil {
				result = &Result{
					IP:     ipAddr,
					Status: ResultStatusError,
					Error:  err.Error(),
				}
				childSpan.SetTag("error", true)
				childSpan.LogFields(log.String("error", err.Error()))
			}
			results[i] = result
			childSpan.LogFields(log.Object("result", result))

			return nil
		})
	}

	if err := group.Wait(); err != nil {
		span.SetTag("error", true)
		span.LogFields(log.String("error", err.Error()))
		fail(c, fasthttp.StatusInternalServerError, err)
	}

	res(c, results)
}

func setRequestIDIfEmpty(c *fasthttp.RequestCtx) {
	if bytes.Equal(c.Request.Header.Peek("X-Request-ID"), nil) {
		c.Request.Header.Set("X-Request-ID", uuid.NewString())
	}
}

func logRequest(c *fasthttp.RequestCtx) {
	klog.Infof("%s - %s [%s] \"%s %s %s\" %d %d \"%s\" \"%s\"",
		// remote_addr
		c.RemoteIP().String(),
		// remote_user
		c.Request.URI().Username(),
		// time_local
		time.Now().Format("02/Jan/2006:15:04:05 -0700"),
		// request
		c.Request.Header.Method(),
		c.Request.URI().Path(),
		c.Request.URI().QueryString(),
		// status
		c.Response.StatusCode(),
		// body_bytes_sent
		c.Response.Header.ContentLength(),
		// http_referer
		c.Request.Header.Referer(),
		// http_user_agent
		c.Request.Header.UserAgent(),
	)
}

func invoke(db *geoip2.Reader, ip net.IP) (*Result, error) {
	rec, err := db.City(ip)
	if err != nil {
		return nil, fmt.Errorf("failed to get city record: %w", err)
	}

	if rec == nil {
		return nil, errors.New("no city record")
	}

	return resolve(rec, ip), nil
}

func fail(ctx *fasthttp.RequestCtx, code int, err error) {
	ctx.SetStatusCode(code)
	ctx.SetBody([]byte(fmt.Sprintf(`{"error": "%v"}`, err)))
}

func res(ctx *fasthttp.RequestCtx, data []any) {
	serialized, err := json.Marshal(map[string]any{"data": data})
	if err != nil {
		fail(ctx, fasthttp.StatusInternalServerError, fmt.Errorf("failed serialize resp: %v", err))

		return
	}

	ctx.SetStatusCode(fasthttp.StatusOK)
	ctx.SetBody(serialized)
}

func resolve(record *geoip2.City, ip net.IP) *Result {
	result := &Result{
		IP:          ip,
		CountryCode: resolveCountryCode(record),
	}

	if result.CountryCode == "" {
		result.Status = ResultStatusNotFound
		return result
	}

	result.Status = ResultStatusFound
	result.Region = resolveRegion(record)
	result.City = resolveCity(record)
	result.Location = resolveLocation(record)
	return result
}

func resolveCountryCode(record *geoip2.City) string {
	return record.Country.IsoCode
}

func resolveRegion(record *geoip2.City) string {
	if len(record.Subdivisions) > 0 {
		if russian, ok := record.Subdivisions[0].Names["ru"]; ok {
			return russian
		} else if english, ok := record.Subdivisions[0].Names["en"]; ok {
			return english
		}
	}

	return ""
}

func resolveCity(record *geoip2.City) string {
	if russian, ok := record.City.Names["ru"]; ok {
		return russian
	} else if english, ok := record.City.Names["en"]; ok {
		return english
	}

	return ""
}

func resolveLocation(record *geoip2.City) *Location {
	var location []string

	if russianCountry, ok := record.Country.Names["ru"]; ok {
		location = append(location, russianCountry)
	} else if englishCountry, ok := record.Country.Names["en"]; ok {
		location = append(location, englishCountry)
	}

	if region := resolveRegion(record); region != "" {
		location = append(location, region)
	}

	if city := resolveCity(record); city != "" {
		location = append(location, city)
	}

	return &Location{
		Coords: [2]float64{record.Location.Latitude, record.Location.Longitude},
		Text:   strings.Join(location, ", "),
	}
}

type Result struct {
	IP          net.IP       `json:"ip"`
	Status      ResultStatus `json:"status"`
	CountryCode string       `json:"country_code,omitempty"`
	Region      string       `json:"region,omitempty"`
	City        string       `json:"city,omitempty"`
	Location    *Location    `json:"location,omitempty"`
	Error       string       `json:"error,omitempty"`
}

type ResultStatus string

const (
	ResultStatusFound    ResultStatus = "found"
	ResultStatusNotFound ResultStatus = "not_found"
	ResultStatusError    ResultStatus = "error"
)

func (t *Result) Empty() bool {
	return t.CountryCode == ""
}

type Location struct {
	Coords [2]float64 `json:"coords,omitempty"`
	Text   string     `json:"text,omitempty"`
}
type Header interface {
	PeekKeys() [][]byte
	PeekAll(string) [][]byte
}

func headers(ctx Header) map[string][]string {
	h := make(map[string][]string)
	for _, header := range ctx.PeekKeys() {
		h[string(header)] = make([]string, 0)
		for _, value := range ctx.PeekAll(string(header)) {
			h[string(header)] = append(h[string(header)], string(value))
		}
	}

	return h
}
