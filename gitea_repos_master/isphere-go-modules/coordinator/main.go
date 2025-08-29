package main

import (
	"context"
	"os"
	"os/signal"
	"syscall"

	"gitea-http.gitea.svc.cluster.local/isphere-go-modules/coordinator/internal"
	"gitea-http.gitea.svc.cluster.local/isphere-go-modules/coordinator/internal/console"
	"go.uber.org/fx"
)

func main() {
	cancelCtx, cancel := context.WithCancel(context.Background())
	defer cancel()

	signals := make(chan os.Signal, 1)
	signal.Notify(signals, os.Interrupt, syscall.SIGTERM)

	go func() {
		<-signals
		cancel()
	}()

	fx.New(
		internal.Module(),
		fx.Invoke(func(application *console.Application) error {
			return application.RunContext(cancelCtx, os.Args)
		}),
	).Run()
}
