package model

import amqp "github.com/rabbitmq/amqp091-go"

const (
	DefaultMessengerConsumeExchangeDeclare = true
	DefaultMessengerConsumeExchangeType    = amqp.ExchangeFanout
	DefaultMessengerConsumeQueueDeclare    = true
	DefaultMessengerConsumeRoutingKey      = ""
)

type MessengerConsumeFlags struct {
	Exchange        string
	ExchangeDeclare bool
	ExchangeType    string
	Queue           string
	QueueDeclare    bool
	RoutingKey      string
}
