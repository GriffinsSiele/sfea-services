package command

import (
	"errors"
	"fmt"
	"net/http"
	"os"
	"time"

	"git.i-sphere.ru/isphere-go-modules/fmsdb/internal/service"
	"github.com/sirupsen/logrus"
	"github.com/urfave/cli/v2"
)

type UpdateSourceCommand struct {
	downloader *service.DownloaderService
	indexer    *service.IndexerService
	migrator   *service.MigratorService
	unarchiver *service.UnarchiverService
}

func NewUpdateSourceCommand(
	downloader *service.DownloaderService,
	indexer *service.IndexerService,
	migrator *service.MigratorService,
	unarchiver *service.UnarchiverService,
) *UpdateSourceCommand {
	return &UpdateSourceCommand{
		downloader: downloader,
		indexer:    indexer,
		migrator:   migrator,
		unarchiver: unarchiver,
	}
}

func (t *UpdateSourceCommand) Describe() *cli.Command {
	return &cli.Command{
		Category: "source",
		Name:     "source:update",
		Action:   t.Action,
		Flags: cli.FlagsByName{
			&cli.StringFlag{
				Name:     "url",
				Required: true,
				EnvVars:  []string{"FMSDB_SOURCE_URL"},
			},
			&cli.StringFlag{
				Name:     "hot-reload",
				Required: false,
			},
			&cli.IntFlag{
				Name:     "retries",
				Required: false,
				EnvVars:  []string{"DOWNLOADING_MAX_RETRIES"},
			},
			&cli.DurationFlag{
				Name:     "retry-timeout",
				Required: false,
				EnvVars:  []string{"DOWNLOADING_TIMEOUT_BETWEEN_RETRIES"},
			},
			&cli.BoolFlag{
				Name:     "recursive",
				Required: false,
				Value:    true,
			},
			&cli.BoolFlag{
				Name:  "auto-clean",
				Value: true,
			},
		},
	}
}

func (t *UpdateSourceCommand) Action(c *cli.Context) error {
	var (
		autoClean    = c.Bool("auto-clean")
		recursive    = c.Bool("recursive")
		retries      = c.Int("retries")
		retryTimeout = c.Duration("retry-timeout")
		url          = c.String("url")
		hotReloadURL = c.String("hot-reload")

		downloadedFilename string // blue
		unarchivedFilename string // blue
		indexFilename      string // blue
		migrationFilename  string // green

		err error
	)

	if autoClean {
		defer func() {
			for _, filename := range []string{downloadedFilename, unarchivedFilename, indexFilename} {
				_ = os.Remove(filename)
			}
		}()
	}

	{ // download source
		logrus.WithField("url", url).Debug("download source")

		for i := 0; i < retries; i++ {
			if downloadedFilename, err = t.downloader.Download(c.Context, url); err == nil {
				break
			}

			logrus.WithError(err).WithField("retry", i).WithField("retry_duration", retryTimeout).Error("download source error")
			time.Sleep(retryTimeout)
		}

		if downloadedFilename == "" {
			return errors.New("failed to download source")
		}

		logrus.WithField("filename", downloadedFilename).Info("file downloaded")
	}

	if !recursive {
		return nil
	}

	{ // unarchive source
		logrus.WithField("filename", downloadedFilename).Debug("unarchive source")

		if unarchivedFilename, err = t.unarchiver.Unarchive(c.Context, downloadedFilename); err != nil {
			return fmt.Errorf("unarchive source: %w", err)
		}

		logrus.WithField("filename", unarchivedFilename).Info("file unarchived")
	}

	{ // build index
		logrus.WithField("filename", unarchivedFilename).Debug("build index")

		if indexFilename, err = t.indexer.Build(c.Context, unarchivedFilename); err != nil {
			return fmt.Errorf("build index: %w", err)
		}

		logrus.WithField("filename", indexFilename).Info("index file created")
	}

	{ // migrate
		logrus.WithField("filename", indexFilename).Debug("migrate")

		if migrationFilename, err = t.migrator.Migrate(c.Context, indexFilename); err != nil {
			return fmt.Errorf("migrate: %w", err)
		}

		logrus.WithField("filename", migrationFilename).Info("migrate was completed")
	}

	{ // hot reload api
		if hotReloadURL != "" {
			req, err := http.NewRequest(http.MethodPost, hotReloadURL, http.NoBody)
			if err != nil {
				logrus.WithError(err).Warnf("failed to create hot reload request")

				return nil
			}

			res, err := http.DefaultClient.Do(req)
			if err != nil {
				logrus.WithError(err).Warnf("failed to send hot reload request")

				return nil
			}

			if res.StatusCode != http.StatusOK {
				logrus.WithError(err).Warnf("unexpected hot reload status code: %d", res.Status)

				return nil
			}
		}
	}

	return nil
}
