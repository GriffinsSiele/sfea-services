package command

import (
	"fmt"

	"git.i-sphere.ru/isphere-go-modules/fmsdb/internal/service"
	"github.com/sirupsen/logrus"
	"github.com/urfave/cli/v2"
)

type MigrateCommand struct {
	migrator *service.MigratorService
}

func NewMigrateCommand(migrator *service.MigratorService) *MigrateCommand {
	return &MigrateCommand{
		migrator: migrator,
	}
}

func (t *MigrateCommand) Describe() *cli.Command {
	return &cli.Command{
		Category: "migrate",
		Name:     "migrate",
		Action:   t.Action,
		Flags: cli.FlagsByName{
			&cli.StringFlag{
				Name:     "filename",
				Required: true,
			},
		},
	}
}

func (t *MigrateCommand) Action(c *cli.Context) error {
	migrationFilename, err := t.migrator.Migrate(c.Context, c.String("filename"))
	if err != nil {
		return fmt.Errorf("migrate: %w", err)
	}

	logrus.WithField("filename", migrationFilename).Info("migrate was completed")

	return nil
}
