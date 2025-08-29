package console

import (
	"context"
	"fmt"
	"os"

	"git.i-sphere.ru/isphere-go-modules/framework/pkg/client"
	"git.i-sphere.ru/isphere-go-modules/framework/pkg/command"
	"git.i-sphere.ru/isphere-go-modules/framework/pkg/contract"
	"git.i-sphere.ru/isphere-go-modules/framework/pkg/decorator"
	"git.i-sphere.ru/isphere-go-modules/framework/pkg/normalizer"
	"git.i-sphere.ru/isphere-go-modules/framework/pkg/storage"
	"git.i-sphere.ru/isphere-go-modules/framework/pkg/validator"
	"go.uber.org/fx"
)

func NewFx(ctx context.Context, opts ...fx.Option) *fx.App {
	// base services
	options := append([]fx.Option{},
		fx.Provide(client.NewHTTP),
		fx.Provide(command.NewHTTPServe),
		fx.Provide(command.NewMessengerConsume),
		fx.Provide(NewApplication),
		fx.Provide(decorator.NewProcessor),
		fx.Provide(fx.Annotate(client.NewHTTPClient, fx.As(new(contract.HTTPClient)))),
		fx.Provide(normalizer.NewPhone),
		fx.Provide(storage.NewKeyDB),
		fx.Provide(validator.NewValidator),

		fx.Provide(func(httpServe *command.HTTPServe, messengerConsume *command.MessengerConsume) []contract.Commander {
			return []contract.Commander{
				httpServe,
				messengerConsume,
			}
		}),
	)

	// extra services
	options = append(options, opts...)

	// invoke application
	options = append(options, fx.Invoke(func(application *Application) error {
		if err := application.RunContext(ctx, os.Args); err != nil {
			return fmt.Errorf("failed to run the application: %w", err)
		}

		return nil
	}))

	return fx.New(options...)
}

func ProvideMessageFactory(constructor any) fx.Option {
	return fx.Provide(fx.Annotate(constructor, fx.As(new(contract.MessageFactory))))
}

func ProvideProcessor(constructor any) fx.Option {
	return fx.Provide(fx.Annotate(constructor, fx.As(new(contract.Processor))))
}
