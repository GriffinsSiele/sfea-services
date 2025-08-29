package contract

import (
	"context"

	amqp "github.com/rabbitmq/amqp091-go"
)

type Sender interface {
	PublishWithContext(context.Context, string, string, bool, bool, amqp.Publishing) error
}
