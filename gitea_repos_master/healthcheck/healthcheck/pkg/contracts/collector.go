package contracts

import "context"

type Collector interface {
	Register(context.Context) error
	Update(context.Context) error
}

const CollectorGroupTag = `group:"collectors"`
