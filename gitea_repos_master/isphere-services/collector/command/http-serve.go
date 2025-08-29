package command

import (
	"bytes"
	"context"
	"net"
	"net/http"
	"runtime"
	"strconv"
	"strings"
	"sync"
	"time"

	"git.i-sphere.ru/isphere-services/collector/client"
	"git.i-sphere.ru/isphere-services/collector/model"
	"github.com/google/uuid"
	"github.com/sirupsen/logrus"
	"github.com/urfave/cli/v2"
	"github.com/valyala/fasthttp"
	"golang.org/x/sync/semaphore"
)

const (
	DefaultHTTPHost   = ""
	DefaultHTTPPort   = 3000
	DefaultMaxRetries = 10
)

type HTTPServe struct {
	amqp *client.AMQP

	itemsChannel chan *model.Item
}

func NewHTTPServe(amqp *client.AMQP) *HTTPServe {
	return &HTTPServe{
		amqp: amqp,

		itemsChannel: make(chan *model.Item),
	}
}

func (t *HTTPServe) Describe() *cli.Command {
	return &cli.Command{
		Category:  "http",
		Name:      "http:serve",
		UsageText: `curl -X POST 127.0.0.1:3000 -d '{"key": "value"}'`,
		Flags: cli.FlagsByName{
			&cli.StringFlag{
				Name:  "host",
				Value: DefaultHTTPHost,
			},
			&cli.IntFlag{
				Name:  "port",
				Value: DefaultHTTPPort,
			},
			&cli.IntFlag{
				Name:  "max-retries",
				Value: DefaultMaxRetries,
			},
		},
		Action: t.Execute,
	}
}

func (t *HTTPServe) Execute(ctx *cli.Context) error {
	var (
		host       = ctx.String("host")
		port       = ctx.Int("port")
		maxRetries = ctx.Int("max-retries")
		addr       = net.JoinHostPort(host, strconv.Itoa(port))
		group      sync.WaitGroup
	)

	group.Add(2)

	go t.consume(context.Background(), maxRetries)

	go func() {
		logrus.WithField("host", host).WithField("port", port).Info("HTTP server started")

		if err := fasthttp.ListenAndServe(addr, t.handler); err != nil {
			logrus.Fatalf("listen HTTP server: %v", err)
		}
	}()

	group.Wait()

	return nil
}

func (t *HTTPServe) handler(c *fasthttp.RequestCtx) {
	reqID := string(c.Request.Header.Peek("X-Request-ID"))
	if reqID == "" {
		reqID = uuid.NewString()
	}

	ctx := context.WithValue(c, "x-request-id", reqID)

	logrus.WithFields(logrus.Fields{
		"remote_addr":     c.RemoteIP().String(),
		"request_body":    string(c.Request.Body()),
		"request_headers": headers(&c.Request.Header),
		"request_id":      ctx.Value("x-request-id"),
		"request_method":  string(c.Request.Header.Method()),
		"request_proto":   string(c.Request.Header.Protocol()),
		"request_time":    time.Now().Format(time.RFC3339),
		"request_uri":     string(c.RequestURI()),
	}).Debug("request")

	defer func() {
		logrus.WithFields(logrus.Fields{
			"request_id":       ctx.Value("x-request-id"),
			"response_body":    string(c.Response.Body()),
			"response_headers": headers(&c.Response.Header),
			"response_status":  c.Response.StatusCode(),
			"response_time":    time.Now().Format(time.RFC3339),
		}).Debug("response")
	}()

	c.SetContentType("application/json")

	path := bytes.TrimLeft(c.Path(), "/")

	if len(path) == 0 {
		c.SetStatusCode(http.StatusBadRequest)

		if _, err := c.WriteString(`{"success": false, "error": "no value for 'exchange' found"}`); err != nil {
			logrus.Fatalf("response: %v", err)
		}

		return
	}

	var (
		exchangeBuilder   strings.Builder
		routingKeyBuilder strings.Builder

		isExchangeBuilderUsing   = true
		isRoutingKeyBuilderUsing = false
	)

	for _, b := range path {
		if b == '/' {
			if isExchangeBuilderUsing {
				isExchangeBuilderUsing = false
				isRoutingKeyBuilderUsing = true

				continue
			} else {
				c.SetStatusCode(http.StatusBadRequest)

				if _, err := c.WriteString(`{"success": false, "error": "path cannot contain symbol '/' more than once"}`); err != nil {
					logrus.Errorf("response: %v", err)
				}

				return
			}
		}

		if isExchangeBuilderUsing {
			exchangeBuilder.WriteByte(b)
		} else if isRoutingKeyBuilderUsing {
			routingKeyBuilder.WriteByte(b)
		}
	}

	if _, err := c.WriteString(`{"success": true}`); err != nil {
		logrus.Errorf("response: %v", err)
	}

	var (
		maxAge int
		err    error
	)

	for _, header := range c.Request.Header.PeekAll("Cache-Control") {
		parts := bytes.Split(header, []byte(","))

		for _, part := range parts {
			if bytes.HasPrefix(part, []byte("max-age=")) {
				maxAgeStr := bytes.TrimPrefix(part, []byte("max-age="))

				if maxAge, err = strconv.Atoi(string(maxAgeStr)); err != nil {
					logrus.WithError(err).Errorf("failed to cast mast age as int: %s", string(maxAgeStr))

					continue
				}

				c.Response.Header.Add("Vary", "Cache-Control")
			}
		}
	}

	c.SetConnectionClose()

	exchange := exchangeBuilder.String()
	if exchange == "healthcheck" {
		return
	}

	t.itemsChannel <- &model.Item{
		Context:     ctx,
		ContentType: string(c.Request.Header.ContentType()),
		Exchange:    exchange,
		RoutingKey:  routingKeyBuilder.String(),
		Payload:     c.Request.Body(),
		MaxAge:      maxAge,
	}
}

// parallel processing by the number of available CPU cores
func (t *HTTPServe) consume(_ context.Context, maxRetries int) {
	sem := semaphore.NewWeighted(int64(runtime.GOMAXPROCS(0)))

	for item := range t.itemsChannel {
		go func(item *model.Item) {
			if err := sem.Acquire(item, 1); err != nil {
				logrus.WithFields(logrus.Fields{
					"request_id": item.Value("x-request-id"),
				}).Fatalf("semaphore acquire: %v", err)
			}

			go func(item *model.Item) {
				defer sem.Release(1)

				if err := t.amqp.Publish(item, item); err != nil {
					logrus.WithFields(logrus.Fields{
						"request_id": item.Value("x-request-id"),
					}).Errorf("AMQP publish: %v", err)

					if item.RetryCount > maxRetries {
						return
					}

					go func(item *model.Item) {
						time.Sleep(10 * time.Second)

						item.RetryCount++

						t.itemsChannel <- item
					}(item)
				}
			}(item)
		}(item)
	}
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
