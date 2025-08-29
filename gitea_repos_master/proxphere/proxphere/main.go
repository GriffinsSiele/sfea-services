package main

import (
	"go.uber.org/fx"

	"go.i-sphere.ru/proxy/pkg/cli"
)

func main() {
	cli.MustLoadEnv()
	fx.New(cli.NewModule()).Run()
}
