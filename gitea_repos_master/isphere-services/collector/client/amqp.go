package client

import (
	"context"
	"fmt"
	"os"
	"strconv"

	"git.i-sphere.ru/isphere-services/collector/model"
	amqp "github.com/rabbitmq/amqp091-go"
	"github.com/sirupsen/logrus"
)

type AMQPRetryKeys string

const AMQPRetryKey AMQPRetryKeys = "amqp_retry"

type AMQP struct {
	nativeConnection *amqp.Connection

	channel *amqp.Channel
}

func NewAMQP() (*AMQP, error) {
	t := &AMQP{}

	if err := t.reconnect(); err != nil {
		return nil, fmt.Errorf("reconnect: %w", err)
	}

	return t, nil
}

func (t *AMQP) Publish(ctx context.Context, item *model.Item) error {
	publishing := amqp.Publishing{
		Body:        item.Payload,
		ContentType: item.ContentType,
		Headers:     amqp.Table{"x-request-id": ctx.Value("x-request-id")},
	}

	headers := map[string][]string{
		"content-type": {item.ContentType},
		"x-request-id": {ctx.Value("x-request-id").(string)},
	}

	if item.MaxAge > 0 {
		publishing.Expiration = strconv.Itoa(item.MaxAge * 1_000)
		headers["cache-control"] = []string{fmt.Sprintf("max-age=%d", item.MaxAge)}
	}

	if err := t.channel.ExchangeDeclare(item.Exchange, "fanout", true, false, false, false, nil); err != nil {
		logrus.WithFields(logrus.Fields{
			"request_id": ctx.Value("x-request-id"),
		}).WithError(err).Error("failed to create exchange dynamically")
	}

	if err := t.channel.PublishWithContext(ctx, item.Exchange, item.RoutingKey, false, false, publishing); err != nil {
		if err = t.reconnect(); err != nil {
			return fmt.Errorf("reconnect: %w", err)
		}

		if _, ok := ctx.Value(AMQPRetryKey).(int); !ok {
			return t.Publish(context.WithValue(ctx, AMQPRetryKey, 1), item)
		}

		return fmt.Errorf("publish message: %w", err)
	}

	logrus.WithFields(logrus.Fields{
		"body":        string(item.Payload),
		"exchange":    item.Exchange,
		"headers":     headers,
		"request_id":  ctx.Value("x-request-id"),
		"retry_count": item.RetryCount,
		"routingKey":  item.RoutingKey,
	}).Debug("publish")

	return nil
}

func (t *AMQP) reconnect() error {
	if t.channel != nil {
		_ = t.channel.Close()
	}

	if t.nativeConnection != nil {
		_ = t.nativeConnection.Close()
	}

	nativeConnection, err := amqp.Dial(os.Getenv("RABBITMQ_DSN"))
	if err != nil {
		return fmt.Errorf("AMQP dial: %w", err)
	}

	t.nativeConnection = nativeConnection

	t.channel, err = t.nativeConnection.Channel()
	if err != nil {
		return fmt.Errorf("AMQP channel: %w", err)
	}

	return nil
}
