package main

import (
	"flag"

	"go.uber.org/fx"
	"i-sphere.ru/proxphere/tor-proxy/internal/config"
	"i-sphere.ru/proxphere/tor-proxy/internal/proxy"
	"i-sphere.ru/proxphere/tor-proxy/internal/tor"
)

var entryNodes string
var exitNodes string
var excludeNodes string
var poolSize int
var maxMemInQueuesMB int
var direct bool

func init() {
	flag.StringVar(&entryNodes, "entry-nodes", "", "comma separated list of entry nodes to use if tor is used")
	flag.StringVar(&exitNodes, "exit-nodes", "", "comma separated list of exit nodes to use if tor is used")
	flag.StringVar(&excludeNodes, "exclude-nodes", "", "comma separated list of nodes to exclude from use if tor is used")
	flag.IntVar(&poolSize, "pool-size", 3, "number of tor connections to use")
	flag.IntVar(&maxMemInQueuesMB, "max-mem-in-queues-mb", 16, "max memory in queues in MB")
	flag.BoolVar(&direct, "direct", false, "don't use tor (use direct proxy)")
	flag.Parse()
}

func main() {
	fx.New(
		fx.Supply(
			config.EntryNodes(entryNodes),
			config.ExitNodes(exitNodes),
			config.ExcludeNodes(excludeNodes),
			config.PoolSize(poolSize),
			config.MaxMemInQueuesMB(maxMemInQueuesMB),
		),
		fx.Provide(
			proxy.NewHandler,
			proxy.NewServer,
			tor.NewPool,
		),
		fx.Invoke(func(*proxy.Server) {}),
	).Run()
}
