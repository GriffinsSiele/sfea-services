package fns

import (
	"context"
	"fmt"

	"github.com/davecgh/go-spew/spew"
	"github.com/tebeka/selenium"
)

type Debtam struct {
}

func NewDebtam() *Debtam {
	return new(Debtam)
}

func (s *Debtam) Name() string {
	return "fns-debtam"
}

func (s *Debtam) Grab(ctx context.Context, driver selenium.WebDriver) error {
	if err := driver.Get("https://www.nalog.ru/opendata/7707329152-debtam/"); err != nil {
		return fmt.Errorf("failed to get page: %w", err)
	}

	element, err := driver.FindElement(selenium.ByCSSSelector, `[property="dc:source"]`)
	if err != nil {
		return fmt.Errorf("failed to find element: %w", err)
	}

	spew.Dump(element.GetAttribute("content"))
	return nil
}
