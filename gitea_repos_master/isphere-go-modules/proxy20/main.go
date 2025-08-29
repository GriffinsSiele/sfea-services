package main

import (
	"context"
	"errors"
	"flag"
	"fmt"
	"os"
	"sync"

	"github.com/getsentry/sentry-go"
	"github.com/joho/godotenv"
	"github.com/sirupsen/logrus"
	"github.com/soulkoden/logrusotel"
	"go.opentelemetry.io/otel/trace"

	commands "i-sphere.ru/proxy/internal/command"
	"i-sphere.ru/proxy/internal/connection"
	"i-sphere.ru/proxy/internal/hook"
	"i-sphere.ru/proxy/internal/repository"
	"i-sphere.ru/proxy/internal/socks5"
	"i-sphere.ru/proxy/internal/tcp"
)

var doGenerateCertificate bool
var doInitDB bool
var doUpdateDB bool
var socks5ServerHost string
var socks5ServerPort uint
var tcpServerHost string
var tcpServerPort uint
var tlsServerHost string
var tlsServerPort uint
var httpServerHost string
var httpServerPort uint

func main() {
	loadEnvFiles()
	parseFlags()
	initLogger()

	if sentryDSN := os.Getenv("SENTRY_DSN"); sentryDSN != "" {
		if err := sentry.Init(sentry.ClientOptions{Dsn: sentryDSN}); err != nil {
			logrus.WithError(err).Fatal("failed to initialize sentry client")
		}

		logrus.AddHook(hook.NewSentryHook(sentryDSN))
	}

	var tracer, tracerSOCKS5, tracerTCP trace.Tracer
	if jaegerEndpoint := os.Getenv("OTEL_EXPORTER_JAEGER_ENDPOINT"); jaegerEndpoint != "" {
		tp, err := logrusotel.NewTracerProvider(jaegerEndpoint, "proxy20", os.Getenv("OTEL_EXPORTER_DEBUG_MODE") == "1")
		if err != nil {
			logrus.WithError(err).Fatal("failed to create tracer provider")
		}
		//goland:noinspection GoUnhandledErrorResult
		defer tp.Shutdown(context.Background())

		tracer = tp.Tracer("proxy20")
		tracerSOCKS5 = tp.Tracer("proxy20/socks5")
		tracerTCP = tp.Tracer("proxy20/tcp")

		logrus.AddHook(logrusotel.NewJaegerHook())
	}

	postgres, err := connection.NewPostgres()
	if err != nil {
		logrus.WithError(err).Fatal("failed to connect to postgres")
	}

	proxyRepo := repository.NewProxy(postgres)

	if doGenerateCertificate {
		command := commands.NewGenerateCertificate(tracer)
		if err := command.Action(context.Background()); err != nil {
			logrus.WithError(err).Fatal("failed to generate certificate")
		}
		return
	} else if doInitDB {
		command := commands.NewInitDB(postgres)
		if err := command.Action(context.Background()); err != nil {
			logrus.WithError(err).Fatal("failed to init database")
		}
		return
	} else if doUpdateDB {
		command := commands.NewUpdateDB(proxyRepo)
		if err := command.Action(context.Background()); err != nil {
			logrus.WithError(err).Fatal("failed to update database")
		}
		return
	}

	var storage sync.Map
	socks5Handler := socks5.NewHandler(proxyRepo, &storage, tracerSOCKS5)
	socks5Server := socks5.NewServer(socks5Handler, tracerSOCKS5)
	tcpHandler := tcp.NewHandler(postgres, proxyRepo, tracerTCP)
	tcpServer := tcp.NewServer(tcpHandler, &storage, tracerTCP)

	errorsChan := make(chan error)
	go func() {
		if err := socks5Server.ListenAndServe(socks5ServerHost, socks5ServerPort); err != nil {
			errorsChan <- fmt.Errorf("failed to listen and serve SOCKS5 server: %w", err)
		}
	}()
	go func() {
		if err := tcpServer.ListenAndServeTCP(tcpServerHost, tcpServerPort); err != nil {
			errorsChan <- fmt.Errorf("failed to listen and serve TCP server: %w", err)
		}
	}()
	go func() {
		if err := tcpServer.ListenAndServeTLS(tlsServerHost, tlsServerPort); err != nil {
			errorsChan <- fmt.Errorf("failed to listen and serve TLS server: %w", err)
		}
	}()
	for err := range errorsChan {
		logrus.WithError(err).Fatal("failed to run servers")
	}
}

func loadEnvFiles() {
	if err := godotenv.Load(".env"); err != nil {
		logrus.WithError(err).Fatal("failed to load .env file")
	}

	if err := godotenv.Overload(".env.local"); err != nil && !errors.Is(err, os.ErrNotExist) {
		logrus.WithError(err).Fatal("failed to load .env.local file")
	}
}

func parseFlags() {
	flag.BoolVar(&doGenerateCertificate, "generate-certificate", false, "Generate certificate")
	flag.BoolVar(&doInitDB, "init-db", false, "Init DB")
	flag.BoolVar(&doUpdateDB, "update-db", false, "Update DB")
	flag.StringVar(&socks5ServerHost, "socks5-server-host", "0.0.0.0", "SOCKS5 server host")
	flag.UintVar(&socks5ServerPort, "socks5-server-port", 1080, "SOCKS server port")
	flag.StringVar(&tlsServerHost, "tls-server-host", "0.0.0.0", "TLS server host")
	flag.UintVar(&tlsServerPort, "tls-server-port", 8080, "TLS server port")
	flag.StringVar(&tcpServerHost, "tcp-server-host", "0.0.0.0", "TCP server host")
	flag.UintVar(&tcpServerPort, "tcp-server-port", 8082, "TCP server port")
	flag.StringVar(&httpServerHost, "http-server-host", "0.0.0.0", "HTTP server host")
	flag.UintVar(&httpServerPort, "http-server-port", 8081, "HTTP server port")
	flag.Parse()
}

func initLogger() {
	level, err := logrus.ParseLevel(os.Getenv("LOG_LEVEL"))
	if err != nil {
		logrus.WithError(err).WithField("level", os.Getenv("LOG_LEVEL")).Fatal("failed to parse log level")
	}

	logrus.SetLevel(level)
}
