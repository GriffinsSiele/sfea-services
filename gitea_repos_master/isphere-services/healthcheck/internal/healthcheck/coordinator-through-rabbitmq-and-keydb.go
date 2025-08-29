package healthcheck

import (
	"context"
	"encoding/json"
	"fmt"
	"net/http"
	"net/url"
	"time"

	"github.com/google/uuid"
	"github.com/rabbitmq/amqp091-go"
	"github.com/redis/go-redis/v9"
	"go.uber.org/atomic"
	"i-sphere.ru/healthcheck/internal/contract"
	"i-sphere.ru/healthcheck/internal/env"
	"i-sphere.ru/healthcheck/internal/model/geoip"
	"i-sphere.ru/healthcheck/internal/storage"
	"i-sphere.ru/healthcheck/internal/util"
)

type CoordinatorThroughRabbitMQAndKeyDB struct {
	params *env.Params
}

func NewCoordinatorThroughRabbitMQAndKeyDB(params *env.Params) *CoordinatorThroughRabbitMQAndKeyDB {
	return &CoordinatorThroughRabbitMQAndKeyDB{
		params: params,
	}
}

func (c *CoordinatorThroughRabbitMQAndKeyDB) Name() string {
	return "coordinator-through-rabbitmq-and-keydb"
}

func (c *CoordinatorThroughRabbitMQAndKeyDB) InspectionInterval() time.Duration {
	return 1 * time.Minute
}

func (c *CoordinatorThroughRabbitMQAndKeyDB) Destinations() []contract.HealthcheckDestination {
	return []contract.HealthcheckDestination{
		contract.HealthcheckDestinationServer,
	}
}

func (c *CoordinatorThroughRabbitMQAndKeyDB) Check(ctx context.Context, events *storage.Events) error {
	requestID := uuid.New()
	events.RequestID = &requestID

	// RabbitMQ connection
	connectionURL := url.URL{
		Scheme: "amqp",
		User:   url.UserPassword(c.params.RabbitMQUsername, c.params.RabbitMQPassword),
		Host:   c.params.RabbitMQHost,
		Path:   c.params.RabbitMQVirtualHost,
	}

	connectionEvent := storage.NewEvent("rabbitmq-connection", c.params.RabbitMQHost)
	connectionStart := time.Now()
	connection, err := amqp091.Dial(connectionURL.String())
	connectionEvent = connectionEvent.WithDuration(time.Since(connectionStart))
	if err != nil {
		events.Append(connectionEvent.WithError(err))
		return fmt.Errorf("failed to connect to RabbitMQ: %w", err)
	}
	//goland:noinspection GoUnhandledErrorResult
	defer connection.Close()
	events.Append(connectionEvent)

	channel, err := connection.Channel()
	if err != nil {
		return fmt.Errorf("failed to open a channel: %w", err)
	}
	//goland:noinspection GoUnhandledErrorResult
	defer channel.Close()

	// KeyDB connection
	client := redis.NewClient(&redis.Options{
		Addr:     c.params.KeyDBHost,
		Password: c.params.KeyDBPassword,
		DB:       c.params.KeyDBDatabase,
	})
	//goland:noinspection GoUnhandledErrorResult
	defer client.Close()

	clientEvent := storage.NewEvent("keydb-connection", c.params.KeyDBHost)
	clientStart := time.Now()
	pingStatus := client.Ping(ctx)
	clientEvent = clientEvent.WithDuration(time.Since(clientStart))
	if pingStatus.Err() != nil {
		events.Append(clientEvent.WithError(pingStatus.Err()))
		return fmt.Errorf("failed to connect to KeyDB: %w", pingStatus.Err())
	}
	events.Append(clientEvent)

	// Check
	ctx, cancel := context.WithTimeout(ctx, 5*time.Second)
	defer cancel()

	request := geoip.NewRequest("185.158.155.34", requestID.String())

	keyDBCtx, cancel := context.WithTimeout(ctx, 30*time.Second)
	var keyDBErr atomic.Error

	go func() {
		defer cancel()

		for {
			select {
			case <-keyDBCtx.Done():
				keyDBErr.Store(keyDBCtx.Err())
				return

			default:
				existsEvent := storage.NewEvent("keydb-exists", c.params.KeyDBHost)
				existsStart := time.Now()
				existsCmd := client.HExists(ctx, "geoip", request.Key)
				exists, err := existsCmd.Result()
				existsEvent = existsEvent.WithDuration(time.Since(existsStart))
				if err != nil {
					events.Append(existsEvent.WithError(err))
					keyDBErr.Store(fmt.Errorf("failed to check if key exists in KeyDB: %w", err))
					return
				} else {
					events.Append(existsEvent.With("response", map[string]any{
						"body": string(util.MustMarshal(map[string]bool{
							"exists": exists,
						})),
					}))
					if !exists {
						time.Sleep(1 * time.Second)
						continue
					}
				}

				getEvent := storage.NewEvent("keydb-get", c.params.KeyDBHost)
				start := time.Now()
				resp, err := client.HGet(ctx, "geoip", request.Key).Result()
				getEvent = getEvent.WithDuration(time.Since(start))
				if err != nil {
					events.Append(getEvent.WithError(err))
					keyDBErr.Store(fmt.Errorf("failed to get response from KeyDB: %w", err))
					return
				}

				getEvent = getEvent.With("response", map[string]any{
					"body": resp,
				})

				var response geoip.Response
				if err := json.Unmarshal([]byte(resp), &response); err != nil {
					events.Append(getEvent.WithError(err))
					keyDBErr.Store(fmt.Errorf("failed to unmarshal response from KeyDB: %w", err))
					return
				}
				if err := response.Validate(); err != nil {
					events.Append(getEvent.WithError(err))
					keyDBErr.Store(fmt.Errorf("invalid response from KeyDB: %w", err))
					return
				}

				events.Append(getEvent)
				return
			}
		}
	}()

	publishEvent := storage.NewEvent("rabbitmq-publish", c.params.RabbitMQHost)
	publishing := amqp091.Publishing{
		Headers: map[string]interface{}{
			"content-type": "application/json",
			"x-request-id": requestID.String(),
		},
		Body: request.Bytes(),
	}
	httpCompatibleHeaders := http.Header{}
	for k, v := range publishing.Headers {
		httpCompatibleHeaders.Add(k, fmt.Sprintf("%v", v))
	}
	publishEvent = publishEvent.With("request", map[string]any{
		"headers": string(util.MustMarshal(util.NormalizeHeaders(httpCompatibleHeaders))),
		"body":    string(request.Bytes()),
	})
	publishStart := time.Now()
	err = channel.PublishWithContext(ctx, "geoip", "", false, false, publishing)
	publishEvent = publishEvent.WithDuration(time.Since(publishStart))
	if err != nil {
		events.Append(publishEvent.WithError(err))
		return fmt.Errorf("failed to publish message: %w", err)
	}
	events.Append(publishEvent)

	<-keyDBCtx.Done()
	if err := keyDBErr.Load(); err != nil {
		return fmt.Errorf("failed to check KeyDB: %w", err)
	}

	return nil
}
