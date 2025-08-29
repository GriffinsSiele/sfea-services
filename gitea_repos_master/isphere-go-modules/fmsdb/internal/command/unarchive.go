package command

import (
	"fmt"

	"git.i-sphere.ru/isphere-go-modules/fmsdb/internal/service"
	"github.com/sirupsen/logrus"
	"github.com/urfave/cli/v2"
)

type UnarchiveCommand struct {
	unarchiver *service.UnarchiverService
}

func NewUnarchiveCommand(unarchiver *service.UnarchiverService) *UnarchiveCommand {
	return &UnarchiveCommand{
		unarchiver: unarchiver,
	}
}

func (t *UnarchiveCommand) Describe() *cli.Command {
	return &cli.Command{
		Category: "unarchive",
		Name:     "unarchive",
		Action:   t.Action,
		Flags: cli.FlagsByName{
			&cli.StringFlag{
				Name:     "filename",
				Required: true,
			},
		},
	}
}

func (t *UnarchiveCommand) Action(c *cli.Context) error {
	unarchivedFilename, err := t.unarchiver.Unarchive(c.Context, c.String("filename"))
	if err != nil {
		return fmt.Errorf("unarchive: %w", err)
	}

	logrus.WithField("filename", unarchivedFilename).Info("unarchived was completed")

	return nil
}
