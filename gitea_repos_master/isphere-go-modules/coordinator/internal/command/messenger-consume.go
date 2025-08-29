package command

import (
	"bytes"
	"context"
	"encoding/json"
	"fmt"
	"net/http"
	"net/url"
	"os"
	"strconv"
	"strings"
	"sync"
	"time"

	"gitea-http.gitea.svc.cluster.local/isphere-go-modules/coordinator/internal/config"
	"gitea-http.gitea.svc.cluster.local/isphere-go-modules/coordinator/internal/contract"
	"gitea-http.gitea.svc.cluster.local/isphere-go-modules/coordinator/internal/manager"
	"gitea-http.gitea.svc.cluster.local/isphere-go-modules/coordinator/internal/model"
	"gitea-http.gitea.svc.cluster.local/isphere-go-modules/coordinator/internal/repository"
	"gitea-http.gitea.svc.cluster.local/isphere-go-modules/coordinator/internal/tags"
	"gitea-http.gitea.svc.cluster.local/isphere-go-modules/coordinator/internal/tracing"
	"gitea-http.gitea.svc.cluster.local/isphere-go-modules/coordinator/internal/util"
	"github.com/google/uuid"
	"github.com/opentracing/opentracing-go/log"
	amqp "github.com/rabbitmq/amqp091-go"
	"github.com/sirupsen/logrus"
	"github.com/urfave/cli/v2"
	"golang.org/x/sync/errgroup"
)

type MessengerConsumeCommand struct {
	cfg              *config.Config
	checkTypeManager *manager.CheckTypeManager
	checkTypeRepo    *repository.CheckTypeRepository

	stateStorage sync.Map
}

func NewMessengerConsumeCommand(
	cfg *config.Config,
	checkTypeManager *manager.CheckTypeManager,
	checkTypeRepo *repository.CheckTypeRepository,
) *MessengerConsumeCommand {
	return &MessengerConsumeCommand{
		cfg:              cfg,
		checkTypeManager: checkTypeManager,
		checkTypeRepo:    checkTypeRepo,
	}
}

func (t *MessengerConsumeCommand) Describe() *cli.Command {
	return &cli.Command{
		Category: "messenger",
		Name:     "messenger:consume",
		Action:   t.Action,
		Flags: cli.FlagsByName{
			&cli.BoolFlag{
				Name:  "auto-setup",
				Value: MessengerConsumeDefaultAutoSetup,
			},
			&cli.StringFlag{
				Name:  "internal-server-addr",
				Value: ":3000",
			},
			&cli.BoolFlag{
				Name:  "use-internal-server",
				Value: true,
			},
			&cli.StringFlag{
				Name:  "exchange",
				Value: MessengerConsumeDefaultExchange,
			},
			&cli.StringFlag{
				Name:  "exchange-type",
				Value: MessengerConsumeDefaultExchangeType,
			},
			&cli.StringSliceFlag{
				Name: "scope",
			},
			&cli.StringSliceFlag{
				Name: "exclude-scope",
			},
		},
	}
}

func (t *MessengerConsumeCommand) Action(c *cli.Context) error {
	ctx := c.Context

	// start consuming
	group, ctx := errgroup.WithContext(ctx)
	ctx, cancel := context.WithCancel(ctx)

	defer cancel()

	if c.Bool("use-internal-server") {
		internalSrv := &http.Server{
			Addr:    c.String("internal-server-addr"),
			Handler: t,
		}
		//goland:noinspection GoUnhandledErrorResult
		defer internalSrv.Shutdown(ctx)
		go func() {
			logrus.WithField("addr", internalSrv.Addr).Info("starting internal server")
			if err := internalSrv.ListenAndServe(); err != nil {
				logrus.WithError(err).Error("failed to listen and serve")
			}
		}()
	}

	// validate listening scope
	allScopes := make([]string, 0)

	for _, checkType := range t.cfg.CheckTypes {
		if !checkType.Upstream.RabbitMQ.Enabled {
			continue
		}

		allScopes = append(allScopes, checkType.Upstream.RabbitMQ.Scope)

		if checkType.Upstream.RabbitMQ.Async.Enabled {
			allScopes = append(allScopes, checkType.Upstream.RabbitMQ.Async.Scope)
		}
	}

	scopes := c.StringSlice("scope")
	if len(scopes) == 0 {
		scopes = allScopes
	}

	for _, scope := range scopes {
		if !util.SliceContains(allScopes, scope) {
			return fmt.Errorf("scope '%s' is invalid, should be one of: '%v'", scope, allScopes)
		}
	}

	excludeScopes := c.StringSlice("exclude-scope")
	if len(excludeScopes) > 0 {
		newScopes := make([]string, 0, len(scopes))
		for _, scope := range scopes {
			if !util.SliceContains(excludeScopes, scope) {
				newScopes = append(newScopes, scope)
			}
		}
		scopes = newScopes
	}

	// make connection
	dsn := url.URL{
		Scheme: "amqp",
		User:   url.UserPassword(t.cfg.Services.RabbitMQ.Username, t.cfg.Services.RabbitMQ.Password),
		Host:   t.cfg.Services.RabbitMQ.Addr,
		Path:   t.cfg.Services.RabbitMQ.VirtualHost,
	}

	connection, err := amqp.Dial(dsn.String())
	if err != nil {
		return fmt.Errorf("AMQP dial: %w", err)
	}

	//goland:noinspection GoUnhandledErrorResult
	defer connection.Close()

	go func() {
		<-connection.NotifyClose(make(chan *amqp.Error))

		cancel()

		logrus.Warn("AMQP connection closed")
	}()

	channel, err := connection.Channel()
	if err != nil {
		return fmt.Errorf("AMQP channel: %w", err)
	}

	//goland:noinspection GoUnhandledErrorResult
	defer channel.Close()

	if c.Bool("auto-setup") {
		if err = t.autoSetup(connection, c.String("exchange"), c.String("exchange-type"), scopes); err != nil {
			return fmt.Errorf("auto setup: %w", err)
		}
	}

	activityCh := make(chan any)

	defer func() {
		close(activityCh)
	}()

	go func() {
		fh, err := os.OpenFile("/tmp/coordinator.lock", os.O_CREATE|os.O_RDWR, 0o0644)

		if err != nil {
			logrus.WithError(err).Error("failed to open lock file")

			return
		}

		defer func() {
			if err := fh.Close(); err != nil {
				logrus.WithError(err).Error("failed to close lock file")
			}
		}()

		for range activityCh {
			if err := os.Chtimes(fh.Name(), time.Now(), time.Now()); err != nil {
				logrus.WithError(err).Error("failed to update mtime on lock file")
			}
		}
	}()

	for _, scope := range scopes {
		scope := scope

		group.Go(func() error {
			ctx = tags.WithGoroutineID(ctx)

			startAt := time.Now()
			checkType, err := t.checkTypeRepo.Find(ctx, scope)

			if err != nil {
				cancel()

				return fmt.Errorf("find check type: %s: %w", scope, err)
			}

			deliveries, err := channel.Consume(scope, "", true, false, false, false, nil)

			if err != nil {
				cancel()

				return fmt.Errorf("consume scope: %s: %w", scope, err)
			}

			logrus.WithField("scope", scope).Info("start consuming")

			t.stateStorage.Swap(scope, &StateInfo{
				Scope:     scope,
				Status:    StateCreated,
				CreatedAt: startAt,
			})

			for {
				select {
				case delivery := <-deliveries:
					if err := t.processDelivery(ctx, channel, scope, c.String("exchange"), checkType, &delivery); err != nil {
						logrus.WithError(err).Error("failed to process delivery")
					}

					activityCh <- new(any)

					processedAt := time.Now()
					t.stateStorage.Swap(scope, &StateInfo{
						Scope:       scope,
						Status:      StateProcessed,
						CreatedAt:   startAt,
						ProcessedAt: &processedAt,
					})

				case <-ctx.Done():
					return nil
				}
			}
		})
	}

	if err = group.Wait(); err != nil {
		return fmt.Errorf("consuming scopes: %w", err)
	}

	return nil
}

func (t *MessengerConsumeCommand) ServeHTTP(w http.ResponseWriter, r *http.Request) {
	stateCollection := &StateCollection{
		States: make([]*StateInfo, 0),
	}

	statuses := map[State]bool{}

	t.stateStorage.Range(func(_, v any) bool {
		if info, ok := v.(*StateInfo); ok {
			stateCollection.States = append(stateCollection.States, info)

			if info.ProcessedAt != nil {
				stateCollection.AtLeastOneProcessed = true
				if stateCollection.LastProcessTime == nil || stateCollection.LastProcessTime.Before(*info.ProcessedAt) {
					stateCollection.LastProcessTime = info.ProcessedAt
				}
			}

			statuses[info.Status] = true

			return true
		}

		return false
	})

	stateCollection.AllStatuses = make([]State, 0, len(statuses))
	for status := range statuses {
		stateCollection.AllStatuses = append(stateCollection.AllStatuses, status)
	}

	w.Header().Set("Content-Type", "application/json")
	if err := json.NewEncoder(w).Encode(stateCollection); err != nil {
		logrus.WithError(err).Error("failed to encode json")
	}
}

func (t *MessengerConsumeCommand) autoSetup(conn *amqp.Connection, exchange, exchangeType string, scopes []string) error {
	makeChannel := func() (*amqp.Channel, error) {
		logrus.Info("try to declare AMQP channel")
		channel, err := conn.Channel()
		if err != nil {
			return nil, fmt.Errorf("AMQP channel: %w", err)
		}
		return channel, nil
	}
	channel, err := makeChannel()
	if err != nil {
		return fmt.Errorf("AMQP channel: %w", err)
	}
	defer channel.Close()

	if err := channel.ExchangeDeclare(exchange, exchangeType, true, false, false, false, nil); err != nil {
		return fmt.Errorf("exchange declare: %s: %w", exchange, err)
	}

	logrus.WithField("internal", true).WithField("exchange", exchange).Info("exchange was declared")

	for _, checkType := range t.cfg.CheckTypes {
		if !checkType.Upstream.RabbitMQ.Enabled {
			continue
		}

		checkTypeScopes := []string{checkType.Upstream.RabbitMQ.Scope}

		if checkType.Upstream.RabbitMQ.Async.Enabled {
			checkTypeScopes = append(checkTypeScopes, checkType.Upstream.RabbitMQ.Async.Scope)
		}

		for _, scope := range checkTypeScopes {
			if !util.SliceContains(scopes, scope) {
				continue
			}

			exchange := scope
			queue := scope

			if err := channel.ExchangeDeclare(exchange, exchangeType, true, false, false, false, nil); err != nil {
				return fmt.Errorf("exchange declare: %s: %w", exchange, err)
			}

			if _, err := channel.QueueDeclare(queue, false, false, false, false, nil); err != nil {
				logrus.WithError(err).Warnf("queue declare: %s: %v", queue, err)
				if channel, err = makeChannel(); err != nil {
					return fmt.Errorf("AMQP channel: %w", err)
				}

				if _, err1 := channel.QueueDelete(queue, false, false, false); err1 != nil {
					return fmt.Errorf("queue delete: %s: %w", queue, err1)
				}
				if _, err1 := channel.QueueDeclare(queue, false, false, false, false, nil); err1 != nil {
					return fmt.Errorf("queue declare: %s: %w", queue, err1)
				}
			}

			logrus.WithField("queue", queue).Info("queue was declared")

			if err := channel.QueueBind(queue, "", exchange, false, nil); err != nil {
				return fmt.Errorf("queue bind: %s -> %s: %w", exchange, queue, err)
			}

			logrus.WithField("exchange", exchange).WithField("queue", queue).Info("queue was bound")
		}
	}

	return nil
}

func (t *MessengerConsumeCommand) addCacheControlVary(headers map[string][]string) {
	if _, ok := headers["vary"]; !ok {
		headers["vary"] = make([]string, 0)
	}

	if !util.SliceContains(headers["vary"], strings.ToLower(contract.CacheControl)) {
		headers["vary"] = append(headers["vary"], strings.ToLower(contract.CacheControl))
	}
}

func (t *MessengerConsumeCommand) processDelivery(ctx context.Context, channel contract.Sender, scope, exchange string, checkType *config.CheckType, delivery *amqp.Delivery) error {
	ctx = tags.WithScope(ctx, scope)

	tracer, closer := tracing.MustTracerCloser()
	defer tracing.MustClose(closer)

	ctx, span := tracing.StartSpanWithContext(ctx, tracer, fmt.Sprintf(`process incoming AMQP message: %s`, scope))
	span.SetTag("scope", scope)
	span.LogFields(log.String("message", string(delivery.Body)))
	defer span.Finish()

	headers, err := t.parseDeliveryHeaders(&ctx, delivery)
	if err != nil {
		return fmt.Errorf("failed to parse delivery headers: %w", util.Fail(span, err))
	}

	response, err := t.checkTypeManager.Apply(ctx, checkType, bytes.NewReader(delivery.Body))
	if err != nil {
		return fmt.Errorf("failed to check type manager to process the message: %w", util.Fail(span, err))
	}

	if ttl := response.Metadata.TTL; ttl != nil {
		if ttl.Age != nil {
			headers["age"] = []string{strconv.Itoa(util.PtrVal(ttl.Age))}
		}
		if ttl.LastModified != nil {
			headers["last-modified"] = []string{util.PtrVal(ttl.LastModified).Format(time.RFC1123)}
		}
		if ttl.ETag != nil {
			headers["etag"] = []string{util.PtrVal(ttl.ETag)}
		}
		if ttl.Expires != nil {
			headers["expires"] = []string{util.PtrVal(ttl.Expires).Format(time.RFC1123)}
		}
	}

	if response.IsIncomplete() {
		span.SetTag("incomplete", true)
		return nil
	}

	if err = t.publishResponse(ctx, response, headers, channel, exchange); err != nil {
		return fmt.Errorf("failed to publish response: %w", util.Fail(span, err))
	}

	return nil
}

func (t *MessengerConsumeCommand) parseDeliveryHeaders(ctx *context.Context, delivery *amqp.Delivery) (map[string][]string, error) {
	tracer, closer := tracing.MustTracerCloser()
	defer tracing.MustClose(closer)

	_, span := tracing.StartSpanWithContext(*ctx, tracer, "parse delivery headers and prepare execution context")
	defer span.Finish()

	headers := map[string][]string{}

	if cacheControlHeader, ok := delivery.Headers[strings.ToLower(contract.CacheControl)].(string); ok {
		if strings.Contains(cacheControlHeader, string(contract.CacheControlNoCache)) {
			t.addCacheControlVary(headers)
			*ctx = context.WithValue(*ctx, contract.CacheControlNoCache, true)
		}
		if strings.Contains(cacheControlHeader, string(contract.CacheControlNoStore)) {
			t.addCacheControlVary(headers)
			*ctx = context.WithValue(*ctx, contract.CacheControlNoStore, true)
		}
		if strings.Contains(cacheControlHeader, string(contract.CacheControlOnlyIfCached)) {
			t.addCacheControlVary(headers)
			*ctx = context.WithValue(*ctx, contract.CacheControlOnlyIfCached, true)
		}
		if contract.MatchCacheControlMaxAge.MatchString(cacheControlHeader) {
			maxAge, err := strconv.Atoi(contract.MatchCacheControlMaxAge.FindString(cacheControlHeader))
			if err != nil {
				return nil, fmt.Errorf("failed to cast max-age as int: %w", util.Fail(span, err))
			}
			t.addCacheControlVary(headers)
			*ctx = context.WithValue(*ctx, contract.CacheControlMaxAge, maxAge)
		}
	}

	if xRequestIDHeader, ok := delivery.Headers[strings.ToLower(contract.ExtraRequestID)].(string); ok {
		*ctx = context.WithValue(*ctx, contract.ExtraRequestIDCtxValue, xRequestIDHeader)
	} else {
		*ctx = context.WithValue(*ctx, contract.ExtraRequestIDCtxValue, uuid.NewString())
	}

	return headers, nil
}

func (t *MessengerConsumeCommand) publishResponse(ctx context.Context, response *model.Response, headers map[string][]string, channel contract.Sender, exchange string) error {
	tracer, closer, _ := tracing.NewTracer()
	defer closer.Close()

	_, span := tracing.StartSpanWithContext(ctx, tracer, "publish response to channel")
	defer span.Finish()

	responseBytes, err := json.Marshal(response)
	if err != nil {
		span.SetTag("error", true)
		span.LogFields(log.String("error", err.Error()))
		return fmt.Errorf("failed to marshal response: %w", err)
	}
	span.LogFields(log.String("body", string(responseBytes)))

	publishingHeaders := amqp.Table{}
	for k, values := range headers {
		publishingHeaders[k] = strings.Join(values, ", ")
	}
	span.LogFields(log.Object("headers", headers))

	err = channel.PublishWithContext(ctx, exchange, "", false, false, amqp.Publishing{
		ContentType: "application/json",
		Headers:     publishingHeaders,
		Body:        responseBytes,
	})
	if err != nil {
		span.SetTag("error", true)
		span.LogFields(log.String("error", err.Error()))
		return fmt.Errorf("failed to publish response: %w", err)
	}

	return nil
}

// ---

const (
	MessengerConsumeDefaultAutoSetup    bool   = true
	MessengerConsumeDefaultExchange     string = "coordinator"
	MessengerConsumeDefaultExchangeType string = "fanout"
)

// ---

type StateInfo struct {
	Scope       string     `json:"scope"`
	Status      State      `json:"status"`
	CreatedAt   time.Time  `json:"created_at"`
	ProcessedAt *time.Time `json:"processed_at"`
}

type State string

const (
	StateCreated   State = "created"
	StateProcessed State = "processed"
)

type StateCollection struct {
	States              []*StateInfo `json:"states"`
	AllStatuses         []State      `json:"all_statuses"`
	AtLeastOneProcessed bool         `json:"at_least_one_processed"`
	LastProcessTime     *time.Time   `json:"last_process_time"`
}
