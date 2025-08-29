package command

import (
	"context"
	"encoding/json"
	"errors"
	"fmt"
	"os"
	"strconv"
	"strings"
	"time"

	"gitea-http.gitea.svc.cluster.local/isphere-go-modules/coordinator/internal/contract"
	"gitea-http.gitea.svc.cluster.local/isphere-go-modules/coordinator/internal/model"
	"gitea-http.gitea.svc.cluster.local/isphere-go-modules/coordinator/internal/repository"
	"github.com/pelletier/go-toml/v2"
	amqp "github.com/rabbitmq/amqp091-go"
	"github.com/sirupsen/logrus"
	"github.com/urfave/cli/v2"
)

type InvokeCommand struct {
	checkTypeRepository     *repository.CheckTypeRepository
	messengerConsumeCommand *MessengerConsumeCommand
}

func NewInvokeCommand(checkTypeRepository *repository.CheckTypeRepository,
	messengerConsumeCommand *MessengerConsumeCommand,
) *InvokeCommand {
	return &InvokeCommand{
		checkTypeRepository:     checkTypeRepository,
		messengerConsumeCommand: messengerConsumeCommand,
	}
}

func (t *InvokeCommand) Describe() *cli.Command {
	return &cli.Command{
		Name:   "invoke",
		Action: t.Action,
		Flags: cli.FlagsByName{
			&cli.StringFlag{
				Name:     "scope",
				Required: true,
			},
		},
	}
}

func (t *InvokeCommand) Action(c *cli.Context) error {
	parameters, err := t.parameters(c.Context, os.Args)
	if err != nil {
		return fmt.Errorf("failed to parse args: %w", err)
	}

	in := map[string]any{
		"id":        1,
		"key":       "",
		"starttime": time.Now().Unix(),
	}

	for k, v := range parameters {
		in["key"] = strings.TrimSpace(fmt.Sprintf("%v %v", in["key"], v))
		in[k] = v
	}

	logrus.WithContext(c.Context).
		WithField("scope", c.String("scope")).
		Info("start processing with parameters")

	inBytes, err := toml.Marshal(in)
	if err != nil {
		return fmt.Errorf("failed to marshal input: %w", err)
	}
	//goland:noinspection GoUnhandledErrorResult
	os.Stdout.Write(inBytes)

	var (
		ctx      = c.Context
		channel  = t.mockChannel(time.Now())
		scope    = c.String("scope")
		exchange = ""
	)

	ctx = context.WithValue(ctx, contract.CacheControlNoCache, 1)
	ctx = context.WithValue(ctx, contract.CacheControlNoStore, 1)

	delivery, err := t.mockDelivery(in)
	if err != nil {
		return fmt.Errorf("failed to mock delivery: %w", err)
	}

	checkType, err := t.checkTypeRepository.Find(ctx, scope)
	if err != nil {
		return fmt.Errorf("failed to find check type by scope: %w", err)
	}

	if err = t.messengerConsumeCommand.processDelivery(ctx, channel, scope, exchange, checkType, delivery); err != nil {
		return fmt.Errorf("failed to process delivery: %w", err)
	}

	return nil
}

func (t *InvokeCommand) parameters(ctx context.Context, args []string) (map[string]any, error) {
	if predefinedValues, ok := ctx.Value(contract.PredefinedValues).(map[string]any); ok {
		return predefinedValues, nil
	}

	var startIndex int

	for i, v := range args {
		if v == "--" {
			startIndex = i + 1

			break
		}
	}

	args = args[startIndex:]
	res := map[string]any{}

	if len(args)%2 != 0 {
		return nil, errors.New("args not balanced")
	}

	for i := 0; i < len(args); i += 2 {
		var (
			key      = strings.TrimPrefix(args[i], "--")
			rawValue = args[i+1]
			value    any
		)

		switch {
		case strings.HasSuffix(rawValue, "::int"):
			intVal, err := strconv.Atoi(strings.TrimSuffix(rawValue, "::int"))
			if err != nil {
				return nil, fmt.Errorf("failed to cast value as int: %w", err)
			}

			value = intVal
		case strings.HasSuffix(rawValue, "::string"):
			value = strings.TrimSuffix(rawValue, "::string")
		default:
			value = rawValue
		}

		res[key] = value
	}

	return res, nil
}

func (t *InvokeCommand) mockChannel(startTime time.Time) contract.Sender {
	return &DummySender{
		startTime: startTime,
	}
}

func (t *InvokeCommand) mockDelivery(in map[string]any) (*amqp.Delivery, error) {
	serialized, err := json.Marshal(in)
	if err != nil {
		return nil, fmt.Errorf("failed to marshal input: %w", err)
	}

	return &amqp.Delivery{
		Headers: amqp.Table{
			"x-cache-control": "no-cache,no-store",
		},
		Body: serialized,
	}, nil
}

// ---

type DummySender struct {
	startTime time.Time
}

func (t *DummySender) PublishWithContext(ctx context.Context, _, _ string, _, _ bool, msg amqp.Publishing) error {
	logrus.WithContext(ctx).
		WithField("duration", time.Since(t.startTime)).
		Info("finish processing and print response")

	var data model.Response

	if err := json.Unmarshal(msg.Body, &data); err != nil {
		return fmt.Errorf("failed to unmarshal data")
	}

	dataBytes, err := json.MarshalIndent(data, "", "  ")
	if err != nil {
		return fmt.Errorf("failed to marshal data: %w", err)
	}
	//goland:noinspection GoUnhandledErrorResult
	os.Stdout.Write(dataBytes)

	return nil
}
