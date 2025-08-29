package connection

import (
	"context"
	"encoding/json"
	"fmt"
	"net/url"
	"os"

	"git.i-sphere.ru/isphere-go-modules/callback/internal/config"
	"github.com/rabbitmq/amqp091-go"
)

type AMQP struct {
	channel    *amqp091.Channel
	connection *amqp091.Connection
	dsn        string
}

func NewAMQP(cfg *config.Config) (*AMQP, error) {
	dsn := url.URL{
		Scheme: "amqp",
		User:   url.UserPassword(cfg.Services.RabbitMQ.Username, cfg.Services.RabbitMQ.Password),
		Host:   cfg.Services.RabbitMQ.Addr,
		Path:   cfg.Services.RabbitMQ.VirtualHost,
	}

	connection, err := amqp091.Dial(dsn.String())
	if err != nil {
		return nil, fmt.Errorf("failed to dial amqp: %w", err)
	}

	ch, err := connection.Channel()
	if err != nil {
		return nil, fmt.Errorf("failed to open amqp channel: %w", err)
	}

	if err = ch.ExchangeDeclare(os.Getenv("DEFAULT_EXCHANGE"), "fanout", true, false, false, false, nil); err != nil {
		return nil, fmt.Errorf("failed to create exchange: %s: %w", os.Getenv("DEFAULT_EXCHANGE"), err)
	}

	if _, err = ch.QueueDeclare(os.Getenv("DEFAULT_QUEUE"), true, false, false, false, nil); err != nil {
		return nil, fmt.Errorf("failed to create queue: %s: %w", os.Getenv("DEFAULT_QUEUE"), err)
	}

	if err = ch.QueueBind(os.Getenv("DEFAULT_QUEUE"), "", os.Getenv("DEFAULT_EXCHANGE"), false, nil); err != nil {
		return nil, fmt.Errorf("failed to bind exchange to queue: %s -> %s: %w", os.Getenv("DEFAULT_EXCHANGE"), os.Getenv("DEFAULT_QUEUE"), err)
	}

	for _, rule := range cfg.Rules {
		if !rule.Downstream.RabbitMQ.Enabled {
			continue
		}

		if err = ch.ExchangeDeclare(rule.Downstream.RabbitMQ.Scope, "fanout", true, false, false, false, nil); err != nil {
			return nil, fmt.Errorf("failed to create exchange: %s: %w", rule.Downstream.RabbitMQ.Scope, err)
		}
	}

	return &AMQP{
		connection: connection,
		channel:    ch,
		dsn:        dsn.String(),
	}, nil
}

func (t *AMQP) Acquire() (*amqp091.Channel, error) {
	var err error

	if t.connection == nil || t.connection.IsClosed() {
		t.channel = nil

		if t.connection, err = amqp091.Dial(t.dsn); err != nil {
			return nil, fmt.Errorf("failed to acquiring dial amqp: %w", err)
		}
	}

	if t.channel == nil || t.channel.IsClosed() {
		if t.channel, err = t.connection.Channel(); err != nil {
			return nil, fmt.Errorf("failed to acquiring amqp channel: %w", err)
		}
	}

	return t.channel, nil
}

func (t *AMQP) Publish(ctx context.Context, ch *amqp091.Channel, msg any) error {
	serialized, err := json.Marshal(msg)
	if err != nil {
		return fmt.Errorf("failed to serialize msg: %w", err)
	}

	if err = ch.PublishWithContext(ctx, os.Getenv("DEFAULT_EXCHANGE"), "", false, false, amqp091.Publishing{
		ContentType: "application/json",
		Headers:     nil,
		Body:        serialized,
	}); err != nil {
		return fmt.Errorf("failed to amqp publish: %w", err)
	}

	return nil
}
