package crawler

import (
	"context"
	"fmt"

	"git.i-sphere.ru/grabber/internal/cli"
	"git.i-sphere.ru/grabber/internal/contracts"
	"github.com/tebeka/selenium"
	"github.com/tebeka/selenium/chrome"
)

type Service struct {
	params *cli.Params
}

func NewService(p *cli.Params) *Service {
	return &Service{
		params: p,
	}
}

func (s *Service) Crawl(ctx context.Context, grabber contracts.Grabber) error {
	driverService, err := selenium.NewChromeDriverService(s.params.ChromeDriverPath, int(s.params.ChromeDriverPort))
	if err != nil {
		return fmt.Errorf("failed to create chrome driver service: %w", err)
	}
	//goland:noinspection GoUnhandledErrorResult
	defer driverService.Stop()

	capabilities := make(selenium.Capabilities)
	capabilities.AddChrome(chrome.Capabilities{
		Args: []string{
			"--headless",
		},
	})

	driver, err := selenium.NewRemote(capabilities, "")
	if err != nil {
		return fmt.Errorf("failed to create chrome driver: %w", err)
	}

	if err = grabber.Grab(ctx, driver); err != nil {
		return fmt.Errorf("failed to call grabber: %s: %w", grabber.Name(), err)
	}

	return nil
}
