package commands

import (
	"context"
	"fmt"
	"sync"

	"git.i-sphere.ru/grabber/internal/contracts"
	"git.i-sphere.ru/grabber/internal/crawler"
	"github.com/urfave/cli/v2"
)

type Download struct {
	crawlerService *crawler.Service
	grabbers       []contracts.Grabber
}

func NewDownload(s *crawler.Service, gs []contracts.Grabber) *Download {
	return &Download{
		crawlerService: s,
		grabbers:       gs,
	}
}

func (d *Download) Describe() (*cli.Command, error) {
	flags := make(cli.FlagsByName, len(d.grabbers))
	for i, g := range d.grabbers {
		flags[i] = &cli.BoolFlag{
			Name: g.Name(),
		}
	}

	return &cli.Command{
		Name:  "download",
		Flags: flags,
	}, nil
}

func (d *Download) Action(ctx *cli.Context) error {
	grabbers := make([]contracts.Grabber, 0, len(d.grabbers))
	for _, g := range d.grabbers {
		if ctx.Bool(g.Name()) {
			grabbers = append(grabbers, g)
		}
	}

	if len(grabbers) == 0 {
		return fmt.Errorf("no grabbers selected")
	}

	var wg sync.WaitGroup
	errCh := make(chan error)

	cancelCtx, cancel := context.WithCancel(ctx.Context)
	defer cancel()

	for _, g := range grabbers {
		wg.Add(1)

		go func(g contracts.Grabber) {
			defer wg.Done()

			if err := d.crawlerService.Crawl(cancelCtx, g); err != nil {
				errCh <- err
			}
		}(g)
	}

	go func() {
		wg.Wait()
		close(errCh)
	}()

	for err := range errCh {
		cancel()
		return err
	}

	return nil
}
