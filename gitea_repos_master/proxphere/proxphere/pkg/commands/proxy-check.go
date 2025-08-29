package commands

import (
	"context"
	"fmt"
	"os"
	"time"

	http "github.com/Danny-Dasilva/fhttp"
	"github.com/charmbracelet/log"
	"github.com/urfave/cli/v2"
	"golang.org/x/sync/errgroup"
	"golang.org/x/sync/semaphore"

	"go.i-sphere.ru/proxy/pkg/managers"
	"go.i-sphere.ru/proxy/pkg/repositories"
)

type ProxyCheck struct {
	proxySpecManager *managers.ProxySpec
	proxySpecRepo    *repositories.ProxySpec

	log *log.Logger

	maxWorkersFlag  *cli.UintFlag
	maxDurationFlag *cli.DurationFlag
}

func NewProxyCheck(proxySpecManager *managers.ProxySpec, proxySpecRepo *repositories.ProxySpec) *ProxyCheck {
	return &ProxyCheck{
		proxySpecManager: proxySpecManager,
		proxySpecRepo:    proxySpecRepo,

		log: log.WithPrefix("commands.ProxyCheck"),

		maxWorkersFlag:  &cli.UintFlag{Name: "max-workers", Value: 10},
		maxDurationFlag: &cli.DurationFlag{Name: "max-duration", Value: 5 * time.Second},
	}
}

func (p *ProxyCheck) NewCommand() *cli.Command {
	return &cli.Command{
		Category: "check",
		Name:     "check/proxy",
		Action:   p.check,
		Flags: cli.FlagsByName{
			p.maxWorkersFlag,
			p.maxDurationFlag,
		},
	}
}

func (p *ProxyCheck) check(c *cli.Context) error {
	proxies, err := p.proxySpecRepo.FindAll(c.Context)
	if err != nil {
		return fmt.Errorf("failed to find proxies: %w", err)
	}
	if len(proxies) == 0 {
		return fmt.Errorf("no proxies found")
	}

	maxWorkers := c.Uint(p.maxWorkersFlag.Name)
	sem := semaphore.NewWeighted(int64(maxWorkers))

	cancelCtx, cancel := context.WithCancel(c.Context)
	defer cancel()

	wg, wgCtx := errgroup.WithContext(cancelCtx)

	maxDuration := c.Duration(p.maxDurationFlag.Name)

	checkCh := make(chan *managers.ProxySpecLogOptions)
	defer close(checkCh)

	go func() {
		for opts := range checkCh {
			logger := p.log.With("proxy_id", opts.ProxySpec.ID, "duration", opts.Duration())
			if opts.Error == nil {
				logger.Debug("pass")
			} else {
				logger.With("error", opts.Error).Warn("fail")
			}

			if err := p.proxySpecManager.LogRequestResponse(opts); err != nil {
				p.log.With("error", err).Error("failed to log request response")
			}
		}
	}()

	for _, proxy := range proxies {
		proxy := proxy

		wg.Go(func() error {
			if err := sem.Acquire(wgCtx, 1); err != nil {
				return fmt.Errorf("failed to acquire semaphore: %w", err)
			}
			defer sem.Release(1)

			cancelCtx, cancel := context.WithTimeout(wgCtx, maxDuration)
			defer cancel()

			req, err := http.NewRequestWithContext(cancelCtx, http.MethodGet,
				os.Getenv("PROXY_CHECK_TEST_DIAL_ENDPOINT"), http.NoBody)
			if err != nil {
				return fmt.Errorf("failed to create request: %w", err)
			}

			logOptions := new(managers.ProxySpecLogOptions).
				WithContext(cancelCtx).
				WithProxySpec(proxy).
				WithRequest(req).
				WithMaster()

			client := &http.Client{
				Transport: &http.Transport{
					Proxy: http.ProxyURL(proxy.URL),
				},
			}

			resp, err := client.Do(req)
			if err != nil {
				logOptions = logOptions.WithError(err)
			} else {
				//goland:noinspection GoUnhandledErrorResult
				defer resp.Body.Close()
				logOptions = logOptions.WithResponse(resp)
			}

			checkCh <- logOptions

			return nil
		})
	}

	if err := wg.Wait(); err != nil {
		return fmt.Errorf("failed to check proxies: %w", err)
	}

	return nil
}
