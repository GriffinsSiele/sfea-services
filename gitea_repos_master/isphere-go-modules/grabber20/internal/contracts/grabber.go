package contracts

import (
	"context"

	"github.com/tebeka/selenium"
)

const grabberGroupTag string = `group:"grabber"`

type Grabber interface {
	Name() string
	Grab(context.Context, selenium.WebDriver) error
}

func AsGrabber(t any) any {
	return AsOne[Grabber](t, grabberGroupTag)
}

func WithGrabbers() any {
	return WithMany[Grabber](grabberGroupTag)
}
