package env

import (
	"flag"
	"fmt"
	"os"
	"strconv"
	"strings"
)

type Params struct {
	HTTPServeAddr string
	SelfAddr      string
	Namespace     string

	DirectHost string

	CoordinatorURL string

	RabbitMQHost,
	RabbitMQPassword,
	RabbitMQUsername,
	RabbitMQVirtualHost string

	KeyDBHost,
	KeyDBPassword string
	KeyDBDatabase int

	MainServiceHost,
	MainServiceUsername,
	MainServicePassword string

	MainServiceDatabaseHost string
	MainServiceDatabasePort uint
	MainServiceDatabaseUsername,
	MainServiceDatabasePassword string
}

func NewParams() (*Params, error) {
	p := new(Params)

	flag.StringVar(&p.HTTPServeAddr, "http-serve-addr", os.Getenv("HTTP_SERVE_ADDR"), "HTTP serve address")
	flag.StringVar(&p.SelfAddr, "self-addr", os.Getenv("SELF_ADDR"), "Self address")
	flag.StringVar(&p.Namespace, "namespace", os.Getenv("NAMESPACE"), "K8S running namespace")

	flag.StringVar(&p.DirectHost, "direct-host", os.Getenv("DIRECT_HOST"), "Direct host")

	flag.StringVar(&p.CoordinatorURL, "coordinator-url", os.Getenv("COORDINATOR_URL"), "Coordinator URL")

	flag.StringVar(&p.RabbitMQHost, "rabbitmq-addr", os.Getenv("RABBITMQ_ADDR"), "RabbitMQ address")
	flag.StringVar(&p.RabbitMQUsername, "rabbitmq-username", os.Getenv("RABBITMQ_USERNAME"), "RabbitMQ username")
	flag.StringVar(&p.RabbitMQPassword, "rabbitmq-password", os.Getenv("RABBITMQ_PASSWORD"), "RabbitMQ password")
	flag.StringVar(&p.RabbitMQVirtualHost, "rabbitmq-virtual-host", os.Getenv("RABBITMQ_VIRTUAL_HOST"), "RabbitMQ virtual host")

	flag.StringVar(&p.KeyDBHost, "keydb-addr", os.Getenv("KEYDB_ADDR"), "KeyDB address")
	flag.StringVar(&p.KeyDBPassword, "keydb-password", os.Getenv("KEYDB_PASSWORD"), "KeyDB password")
	defaultKeyDBDatabase, err := strconv.Atoi(os.Getenv("KEYDB_DATABASE"))
	if err != nil {
		return nil, fmt.Errorf("failed to parse KEYDB_DATABASE: %w", err)
	}
	flag.IntVar(&p.KeyDBDatabase, "keydb-database", defaultKeyDBDatabase, "KeyDB database")

	flag.StringVar(&p.MainServiceHost, "main-service-addr", os.Getenv("MAIN_SERVICE_ADDR"), "MainService address")
	flag.StringVar(&p.MainServiceUsername, "main-service-username", os.Getenv("MAIN_SERVICE_USERNAME"), "MainService username")
	flag.StringVar(&p.MainServicePassword, "main-service-password", os.Getenv("MAIN_SERVICE_PASSWORD"), "MainService password")

	flag.StringVar(&p.MainServiceDatabaseHost, "main-service-database-host", os.Getenv("MAIN_SERVICE_DATABASE_HOST"), "MainService database host")
	defaultMainServiceDatabasePort, err := strconv.ParseUint(os.Getenv("MAIN_SERVICE_DATABASE_PORT"), 10, 32)
	if err != nil {
		return nil, fmt.Errorf("failed to parse MAIN_SERVICE_DATABASE_PORT: %w", err)
	}
	flag.UintVar(&p.MainServiceDatabasePort, "main-service-database-port", uint(defaultMainServiceDatabasePort), "MainService database port")
	flag.StringVar(&p.MainServiceDatabaseUsername, "main-service-database-username", os.Getenv("MAIN_SERVICE_DATABASE_USERNAME"), "MainService database username")
	flag.StringVar(&p.MainServiceDatabasePassword, "main-service-database-password", os.Getenv("MAIN_SERVICE_DATABASE_PASSWORD"), "MainService database password")

	args := os.Args[1:]
	if !strings.HasPrefix(args[0], "-") {
		args = args[1:]
	}

	if err := flag.CommandLine.Parse(args); err != nil {
		return nil, fmt.Errorf("failed to parse CLI parameters: %w", err)
	}

	return p, nil
}
