package main

import (
	"context"

	"git.i-sphere.ru/isphere-go-modules/framework/pkg/console"
	"git.i-sphere.ru/isphere-go-modules/framework/pkg/runtime"
	"git.i-sphere.ru/isphere-go-modules/phones/internal"
	"github.com/sirupsen/logrus"
)

func main() {
	if err := runtime.Invoke(func(ctx context.Context) error {
		console.NewFx(ctx,
			console.ProvideMessageFactory(internal.NewMessageFactory),
			console.ProvideProcessor(internal.NewProcessor),
		).Run()

		return nil
	}); err != nil {
		logrus.WithError(err).Fatalf("the application has been accidentally terminated: %v", err)
	}
}
