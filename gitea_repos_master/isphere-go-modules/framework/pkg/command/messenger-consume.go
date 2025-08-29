package command

import (
	"context"
	"encoding/json"
	"fmt"
	"strconv"
	"strings"

	"git.i-sphere.ru/isphere-go-modules/framework/pkg/contract"
	"git.i-sphere.ru/isphere-go-modules/framework/pkg/decorator"
	"git.i-sphere.ru/isphere-go-modules/framework/pkg/model"
	"git.i-sphere.ru/isphere-go-modules/framework/pkg/runtime"
	"git.i-sphere.ru/isphere-go-modules/framework/pkg/validator"
	amqp "github.com/rabbitmq/amqp091-go"
	"github.com/sirupsen/logrus"
	"github.com/urfave/cli/v2"
	"golang.org/x/sync/errgroup"
)

type MessengerConsume struct {
	messageFactory contract.MessageFactory
	processor      *decorator.Processor
	validator      *validator.Validator
}

func NewMessengerConsume(messageFactory contract.MessageFactory, processor *decorator.Processor, validator *validator.Validator) *MessengerConsume {
	return &MessengerConsume{
		messageFactory: messageFactory,
		processor:      processor,
		validator:      validator,
	}
}

func (t *MessengerConsume) Command() *cli.Command {
	return &cli.Command{
		Category: "messenger",
		Name:     "messenger:consume",
		Action:   t.action,
		Flags: cli.FlagsByName{
			// exchange options
			&cli.StringFlag{
				Name:     "exchange",
				Required: true,
			},
			&cli.StringFlag{
				Name:  "exchange-type",
				Value: model.DefaultMessengerConsumeExchangeType,
			},
			&cli.BoolFlag{
				Name:  "exchange-declare",
				Value: model.DefaultMessengerConsumeExchangeDeclare,
			},

			// queue options
			&cli.StringFlag{
				Name:     "queue",
				Required: true,
			},
			&cli.BoolFlag{
				Name:  "queue-declare",
				Value: model.DefaultMessengerConsumeQueueDeclare,
			},

			// binding options
			&cli.StringFlag{
				Name:  "routing-key",
				Value: model.DefaultMessengerConsumeRoutingKey,
			},
		},
	}
}

func (t *MessengerConsume) action(ctx *cli.Context) error {
	flags := &model.MessengerConsumeFlags{
		Exchange:        ctx.String("exchange"),
		ExchangeDeclare: ctx.Bool("exchange-declare"),
		ExchangeType:    ctx.String("exchange-type"),
		Queue:           ctx.String("queue"),
		QueueDeclare:    ctx.Bool("queue-declare"),
		RoutingKey:      ctx.String("routing-key"),
	}

	connection, err := amqp.Dial(runtime.URL("MESSENGER_TRANSPORT_DSN").String())
	if err != nil {
		return fmt.Errorf("could not open an AMQP connection: %w", err)
	}

	//goland:noinspection GoUnhandledErrorResult
	defer connection.Close()

	go func() {
		<-connection.NotifyClose(make(chan *amqp.Error))

		logrus.Warn("AMQP connection was closed")
	}()

	channel, err := connection.Channel()
	if err != nil {
		return fmt.Errorf("could not open an AMQP channel: %w", err)
	}

	//goland:noinspection GoUnhandledErrorResult
	defer channel.Close()

	if err := t.configure(flags, channel); err != nil {
		return fmt.Errorf("failed to configure the AMQP connection: %w", err)
	}

	deliveries, err := channel.Consume(flags.Queue, "", true, false, false, false, nil)
	if err != nil {
		return fmt.Errorf("could not start listening AMQP message: %w", err)
	}

	var group errgroup.Group

	group.Go(func() error {
		for delivery := range deliveries {
			message := t.messageFactory.New()

			if err := json.Unmarshal(delivery.Body, &message); err != nil {
				logrus.WithError(err).Errorf("failed to unmarshal the AMQP message: %v", err)

				continue
			}

			if err := t.validator.Struct(message); err != nil {
				logrus.WithError(err).Errorf("the AMQP message does not validate: %v", err)

				continue
			}

			processorCtx := ctx.Context

			if cacheControl, ok := delivery.Headers["cache-control"].(string); ok {
				if strings.Contains(cacheControl, string(contract.CacheControlNoCache)) {
					processorCtx = context.WithValue(processorCtx, contract.CacheControlNoCache, new(any))
				}

				if strings.Contains(cacheControl, string(contract.CacheControlNoStore)) {
					processorCtx = context.WithValue(processorCtx, contract.CacheControlNoStore, new(any))
				}

				if strings.Contains(cacheControl, string(contract.CacheControlOnlyIfCached)) {
					processorCtx = context.WithValue(processorCtx, contract.CacheControlOnlyIfCached, new(any))
				}

				if contract.MatchCacheControlMaxAge.MatchString(cacheControl) {
					maxAgeString := contract.MatchCacheControlMaxAge.FindString(cacheControl)

					maxAge, err := strconv.Atoi(maxAgeString)
					if err != nil {
						logrus.WithError(err).Error("failed to cast max-age header on AMQP message")

						continue
					}

					processorCtx = context.WithValue(processorCtx, contract.CacheControlMaxAge, maxAge)
				}
			}

			response, err := t.processor.Invoke(processorCtx, message)
			if err != nil {
				logrus.WithError(err).Errorf("failed to process the AMQP message: %v", err)

				continue
			}

			logrus.WithField("message", message).WithField("response", response).Info("message was processed")
		}

		return nil
	})

	if err := group.Wait(); err != nil {
		return fmt.Errorf("unexpected AMQP consumer error: %w", err)
	}

	return nil
}

func (t *MessengerConsume) configure(flags *model.MessengerConsumeFlags, channel *amqp.Channel) error {
	if flags.ExchangeDeclare {
		if err := channel.ExchangeDeclare(flags.Exchange, flags.ExchangeType, true, false, false, false, nil); err != nil {
			return fmt.Errorf("failed to declare an AMQP exchange: %w", err)
		}
	}

	if flags.QueueDeclare {
		if _, err := channel.QueueDeclare(flags.Queue, true, false, false, false, nil); err != nil {
			return fmt.Errorf("failed to declare an AMQP queue: %w", err)
		}
	}

	if err := channel.QueueBind(flags.Queue, flags.RoutingKey, flags.Exchange, false, nil); err != nil {
		return fmt.Errorf("failed to bind the AMQP queue: %w", err)
	}

	return nil
}
