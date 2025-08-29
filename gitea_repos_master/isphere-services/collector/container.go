package main

import (
	"fmt"

	"git.i-sphere.ru/isphere-services/collector/cli"
	"git.i-sphere.ru/isphere-services/collector/client"
	"git.i-sphere.ru/isphere-services/collector/command"
	"git.i-sphere.ru/isphere-services/collector/contract"
	"go.uber.org/dig"
)

type Container struct {
	wrappedContainer *dig.Container
}

func NewContainer() *Container {
	return &Container{}
}

func (t *Container) Initialize() error {
	t.wrappedContainer = dig.New()
	for _, definition := range t.definitions() {
		if err := t.wrappedContainer.Provide(definition); err != nil {
			return fmt.Errorf("definition: %w", err)
		}
	}

	return nil
}

func (t *Container) definitions() []any {
	return []any{
		func() *dig.Container {
			return t.wrappedContainer
		},

		cli.NewApp,
		client.NewAMQP,
		command.NewHTTPServe,

		func(httpServe *command.HTTPServe) []contract.Commander {
			return []contract.Commander{
				httpServe,
			}
		},
	}
}
