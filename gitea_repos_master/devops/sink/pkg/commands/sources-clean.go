package commands

import (
	"fmt"
	"os"
	"sink/pkg/clients"
	"sink/pkg/contracts"

	"github.com/charmbracelet/log"
	"github.com/urfave/cli/v2"
	"gopkg.in/yaml.v3"
)

type SourcesClean struct {
	debeziumClient *clients.Debezium
}

func NewSourcesClean(debeziumClient *clients.Debezium) contracts.Invoker {
	return &SourcesClean{
		debeziumClient: debeziumClient,
	}
}

func (c *SourcesClean) Invoke(ctx *cli.Context) error {
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
		if err = c.debeziumClient.DeleteSourceByName(ctx.Context, option.Name); err != nil {
			log.Error("failed to delete source", "error", err)
			continue
		}
	}

	return nil
}
