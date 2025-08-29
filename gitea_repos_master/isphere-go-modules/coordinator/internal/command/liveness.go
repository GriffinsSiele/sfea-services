package command

import (
	"errors"
	"fmt"
	"os"
	"time"

	"gitea-http.gitea.svc.cluster.local/isphere-go-modules/coordinator/internal/config"
	"github.com/sirupsen/logrus"
	"github.com/urfave/cli/v2"
)

type LivenessCommand struct {
	cfg *config.Config
}

func NewLivenessCommand(cfg *config.Config) *LivenessCommand {
	return &LivenessCommand{
		cfg: cfg,
	}
}

func (t *LivenessCommand) Describe() *cli.Command {
	return &cli.Command{
		Category: "liveness",
		Name:     "liveness",
		Action:   t.Action,
	}
}

func (t *LivenessCommand) Action(c *cli.Context) error {
	if err := t.probeLastMessage(); err != nil {
		return fmt.Errorf("failed to probe last message: %w", err)
	}
	return nil
}

func (t *LivenessCommand) probeLastMessage() error {
	defer func() {
		logrus.Info("[probe] Probe file Done")
	}()

	if err := checkProbeFile(); err != nil {
		logrus.WithError(err).Warn("no check file provided, needs for probe later")
		return nil
	}

	return nil
}

func checkProbeFile() error {
	info, err := os.Stat("/tmp/coordinator.lock")
	if err != nil {
		return err
	}

	if time.Since(info.ModTime()).Minutes() > 10 {
		return errors.New("probe file was expired")
	}

	return nil
}
