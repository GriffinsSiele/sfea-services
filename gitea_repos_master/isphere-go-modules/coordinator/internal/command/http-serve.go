package command

import (
	"bytes"
	"context"
	"encoding/json"
	"fmt"
	"io"
	"log/slog"
	"net"
	"net/http"
	"net/url"
	"os"
	"strconv"
	"strings"
	"time"

	"gitea-http.gitea.svc.cluster.local/isphere-go-modules/coordinator/internal/config"
	"gitea-http.gitea.svc.cluster.local/isphere-go-modules/coordinator/internal/contract"
	"github.com/gin-contrib/cors"
	"github.com/gin-gonic/gin"
	"github.com/google/uuid"
	"github.com/prometheus/client_golang/prometheus"
	"github.com/prometheus/client_golang/prometheus/promauto"
	"github.com/sirupsen/logrus"
	"github.com/urfave/cli/v2"
	ginprometheus "github.com/zsais/go-gin-prometheus"
)

type HTTPServeCommand struct {
	controllers []contract.Controller
	cfg         *config.Config
}

func NewHTTPServeCommand(controllers []contract.Controller, cfg *config.Config) *HTTPServeCommand {
	return &HTTPServeCommand{
		controllers: controllers,
		cfg:         cfg,
	}
}

func (t *HTTPServeCommand) Describe() *cli.Command {
	return &cli.Command{
		Category: "http",
		Name:     "http:serve",
		Action:   t.Action,
		Flags: cli.FlagsByName{
			&cli.StringFlag{
				Name:  "host",
				Value: HTTPServeDefaultHost,
			},
			&cli.IntFlag{
				Name:  "port",
				Value: HTTPServeDefaultPort,
			},
		},
	}
}

func (t *HTTPServeCommand) Action(ctx *cli.Context) error {
	var (
		addr   = net.JoinHostPort(ctx.String("host"), strconv.Itoa(ctx.Int("port")))
		router = gin.Default()
	)

	router.Use(requestID)
	router.Use(logger)
	router.Use(cors.New(cors.Config{
		AllowOriginFunc: func(origin string) bool {
			u, err := url.Parse(origin)
			if err != nil {
				logrus.WithField("origin", origin).Error("invalid origin")
				return false
			}
			hostname := u.Hostname()
			return hostname == "localhost" ||
				hostname == "127.0.0.1" ||
				strings.HasSuffix(hostname, "svc.cluster.local")
		},
		AllowMethods:     []string{"GET", "OPTIONS"},
		AllowHeaders:     []string{"Origin", "Content-Type", "Accept", "Authorization"},
		ExposeHeaders:    []string{"Content-Length"},
		AllowCredentials: true,
		MaxAge:           12 * time.Hour,
	}))

	p := ginprometheus.NewPrometheus("coordinator", newRabbitQueueMetric(ctx.Context, t.cfg))
	p.Use(router)

	for _, ctrl := range t.controllers {
		ctrl.Describe(router)
	}

	srv := &http.Server{
		Addr:    addr,
		Handler: router.Handler(),
	}

	go func() {
		if err := srv.ListenAndServe(); err != nil {
			logrus.WithError(err).Error("http serve error")
		}
	}()

	<-ctx.Done()
	//goland:noinspection GoUnhandledErrorResult
	srv.Shutdown(ctx.Context)
	return ctx.Err()
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

// ---

var rabbitQueueMetric = promauto.NewGaugeVec(
	prometheus.GaugeOpts{
		Name: "coordinator_rabbit_queue_size",
		Help: "The number of messages in the queue",
	},
	[]string{
		"scope",
	},
)

var rabbitQueueAverageRate = promauto.NewGaugeVec(
	prometheus.GaugeOpts{
		Name: "coordinator_rabbit_queue_average_rate",
		Help: "The average rate of messages in the queue",
	},
	[]string{
		"scope",
	},
)

func newRabbitQueueMetric(ctx context.Context, cfg *config.Config) []*ginprometheus.Metric {
	go func() {
		for {
			select {
			case <-ctx.Done():
				return
			case <-time.After(30 * time.Second):
				break
			}

			host, _, err := net.SplitHostPort(os.Getenv("RABBITMQ_ADDR"))
			if err != nil {
				slog.With("error", err).Error("failed to parse RABBITMQ_ADDR")
				continue
			}

			//goland:noinspection HttpUrlsUsage
			u := fmt.Sprintf("http://%s:%s@%s:%d/api/queues/%s?msg_rates_age=300&msg_rates_incr=100",
				os.Getenv("RABBITMQ_USERNAME"),
				os.Getenv("RABBITMQ_PASSWORD"),
				host, 15672,
				url.QueryEscape(os.Getenv("RABBITMQ_VIRTUAL_HOST")))

			req, err := http.NewRequestWithContext(ctx, http.MethodGet, u, http.NoBody)
			if err != nil {
				slog.With("error", err).Error("failed to create request")
				continue
			}

			resp, err := http.DefaultClient.Do(req)
			if err != nil {
				slog.With("error", err).Error("failed to perform request")
				continue
			}

			var responses []rabbitmqQueueResp
			err = json.NewDecoder(resp.Body).Decode(&responses)
			//goland:noinspection GoUnhandledErrorResult
			resp.Body.Close()
			if err != nil {
				slog.With("error", err).Error("failed to decode response")
				continue
			}

			for _, response := range responses {
				rabbitQueueMetric.With(prometheus.Labels{"scope": response.Scope}).
					Set(float64(response.MessagesReady + response.MessagesUnacknowledged))

				rabbitQueueAverageRate.With(prometheus.Labels{"scope": response.Scope}).
					Set(response.MessageStats.PublishDetails.AverageRate)
			}
		}
	}()

	return []*ginprometheus.Metric{
		{
			Name:        "coordinator_rabbit_queue_size",
			Description: "The number of messages in the queue",
			Type:        "gauge_vec",
		},
		{
			Name:        "coordinator_rabbit_queue_average_rate",
			Description: "The average rate of messages in the queue",
			Type:        "gauge_vec",
		},
	}
}

type rabbitmqQueueResp struct {
	Scope                  string `json:"name"`
	MessagesReady          int    `json:"messages_ready"`
	MessagesUnacknowledged int    `json:"messages_unacknowledged"`
	MessageStats           struct {
		PublishDetails struct {
			Average     float64 `json:"avg"`
			AverageRate float64 `json:"avg_rate"`
			Rate        float64 `json:"rate"`
		} `json:"publish_details"`
	} `json:"message_stats"`
}

// ---

type responseBodyWriter struct {
	gin.ResponseWriter
	body *bytes.Buffer
}

func (t *responseBodyWriter) Write(b []byte) (int, error) {
	t.body.Write(b)

	return t.ResponseWriter.Write(b)
}

// ---

const (
	HTTPServeDefaultHost = ""
	HTTPServeDefaultPort = 3000
)
