package commands

import (
	"encoding/json"
	"fmt"
	"os"
	"sink/pkg/clients"
	"sink/pkg/contracts"
	"sink/pkg/models/debezium"
	"strings"

	"github.com/charmbracelet/log"
	"github.com/urfave/cli/v2"
	"gopkg.in/yaml.v3"
)

type SourcesConfigure struct {
	debeziumClient *clients.Debezium
}

func NewSourcesConfigure(debeziumClient *clients.Debezium) contracts.Invoker {
	return &SourcesConfigure{
		debeziumClient: debeziumClient,
	}
}

func (c *SourcesConfigure) Invoke(ctx *cli.Context) error {
	sourcesFile, err := os.Open("config/sources.yaml")
	if err != nil {
		return fmt.Errorf("failed to open config/sources.yaml: %w", err)
	}
	//goland:noinspection GoUnhandledErrorResult
	defer sourcesFile.Close()

	var options []*Option
	if err = yaml.NewDecoder(sourcesFile).Decode(&options); err != nil {
		return fmt.Errorf("failed to parse config/sources.yaml: %w", err)
	}

	for _, option := range options {
		source := c.newSource(option.Name, option.DatabaseServerID, option.Tables)
		sourceBytes, err := json.Marshal(source)
		if err != nil {
			log.Error("failed to marshal source", "error", err)
			continue
		}

		if err = c.debeziumClient.CreateSource(ctx.Context, sourceBytes); err != nil {
			log.Error("failed to create source", "error", err)
			continue
		}
	}

	return nil
}

func (c *SourcesConfigure) newSource(name string, databaseServerID uint, tables []string) map[string]any {
	databasesMap := make(map[string]struct{})
	for _, table := range tables {
		database, _, _ := strings.Cut(table, ".")
		databasesMap[database] = struct{}{}
	}
	databases := make([]string, 0, len(databasesMap))
	for database := range databasesMap {
		databases = append(databases, database)
	}

	options := map[string]any{
		"connector.class": debezium.MySqlConnectorClass,
		"tasks.max":       1,

		"database.hostname":           os.Getenv("SOURCE_MYSQL_DATABASE_HOSTNAME"),
		"database.port":               os.Getenv("SOURCE_MYSQL_DATABASE_PORT"),
		"database.user":               os.Getenv("SOURCE_MYSQL_DATABASE_USERNAME"),
		"database.password":           os.Getenv("SOURCE_MYSQL_DATABASE_PASSWORD"),
		"database.connectionTimeZone": "Europe/Moscow",

		"topic.prefix": name,
		"topic.creation.default.replication.factor": -1,
		"topic.creation.default.partitions":         -1,
		"topic.creation.default.cleanup.policy":     "compact",

		"database.server.id": databaseServerID,
		//"database.server.name": "",

		"database.include.list": debezium.PlainList(databases),
		"table.include.list":    debezium.PlainList(tables),

		"column.propagate.source.type": true,
		"include.schema.changes":       false,
		"quote.identifiers":            true,

		"database.history.kafka.bootstrap.servers": "kafka:9092",
		"database.history.kafka.topic":             "database-history." + name,

		"schema.history.internal.kafka.bootstrap.servers": "kafka:9092",
		"schema.history.internal.kafka.topic":             "schema-history-internal." + name,
	}

	return map[string]any{
		"name":   name,
		"config": options,
	}
}

type Option struct {
	Name             string   `yaml:"name"`
	DatabaseServerID uint     `yaml:"databaseServerID"`
	Tables           []string `yaml:"tables"`
}
