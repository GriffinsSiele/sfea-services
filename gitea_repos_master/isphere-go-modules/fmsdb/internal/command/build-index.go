package command

import (
	"fmt"

	"git.i-sphere.ru/isphere-go-modules/fmsdb/internal/service"
	"github.com/sirupsen/logrus"
	"github.com/urfave/cli/v2"
)

type BuildIndexCommand struct {
	indexer *service.IndexerService
}

func NewBuildIndexCommand(indexer *service.IndexerService) *BuildIndexCommand {
	return &BuildIndexCommand{
		indexer: indexer,
	}
}

func (t *BuildIndexCommand) Describe() *cli.Command {
	return &cli.Command{
		Category: "index",
		Name:     "index:build",
		Action:   t.Action,
		Flags: cli.FlagsByName{
			&cli.StringFlag{
				Name:     "filename",
				Required: true,
			},
		},
	}
}

func (t *BuildIndexCommand) Action(c *cli.Context) error {
	indexFilename, err := t.indexer.Build(c.Context, c.String("filename"))
	if err != nil {
		return fmt.Errorf("build index: %w", err)
	}

	logrus.WithField("filename", indexFilename).Info("index file created")

	return nil
}
