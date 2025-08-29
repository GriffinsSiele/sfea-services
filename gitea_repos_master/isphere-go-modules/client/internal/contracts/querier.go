package contracts

import (
	"fmt"

	"github.com/graphql-go/graphql"
	"go.uber.org/fx"
)

const QuerierTag = `group:"querier"`

type Querier interface {
	fmt.Stringer
	Query() *graphql.Field
}

func AsQuerier(t any) any {
	return fx.Annotate(t, fx.As(new(Querier)), fx.ResultTags(QuerierTag))
}
