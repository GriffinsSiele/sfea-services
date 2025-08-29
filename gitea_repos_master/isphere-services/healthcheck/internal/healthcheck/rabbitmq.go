package healthcheck

import (
	"context"
	"fmt"
	"net"
	"time"

	"github.com/google/uuid"
	"i-sphere.ru/healthcheck/internal/client"
	"i-sphere.ru/healthcheck/internal/contract"
	"i-sphere.ru/healthcheck/internal/env"
	"i-sphere.ru/healthcheck/internal/storage"
)

type RabbitMQ struct {
	direct *client.Direct
	params *env.Params
}

func NewRabbitMQ(direct *client.Direct, params *env.Params) *RabbitMQ {
	return &RabbitMQ{
		direct: direct,
		params: params,
	}
}

func (k *RabbitMQ) Name() string {
	return "rabbitmq"
}

func (k *RabbitMQ) InspectionInterval() time.Duration {
	return 1 * time.Minute
}

func (k *RabbitMQ) Destinations() []contract.HealthcheckDestination {
	return []contract.HealthcheckDestination{
		contract.HealthcheckDestinationNodeWorker,
	}
}

func (k *RabbitMQ) Check(ctx context.Context, events *storage.Events) error {
	requestID := uuid.New()
	events.RequestID = &requestID

	hostname, _, _ := net.SplitHostPort(k.params.RabbitMQHost)
	ptrs, err := k.direct.LookupPTR(hostname)
	if err != nil {
		return fmt.Errorf("failed to lookup PTR: %w", err)
	}

	for _, ptr := range ptrs {
		wait := make(chan bool)

		go func(ptr *client.PTR) {
			defer close(wait)
			ctx, cancel := context.WithTimeout(ctx, 1*time.Minute)
			defer cancel()

			event := storage.NewEvent("rabbitmq-dial", ptr.String()).
				With("node", ptr.NodeName).
				With("hostname", ptr.String())

			start := time.Now()
			dial, err := (k.direct.NewDialer()).DialContext(ctx, "tcp", fmt.Sprintf("%s:5672", ptr.String()))
			event = event.WithDuration(time.Since(start))
			if err != nil {
				events.Append(event.WithError(err))
				return
			}
			//goland:noinspection GoUnhandledErrorResult
			defer dial.Close()
			events.Append(event)
		}(ptr)

		<-wait
	}

	return nil
}
