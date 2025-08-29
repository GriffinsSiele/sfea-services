package internal

import (
	"fmt"
	"net"

	"github.com/graphql-go/graphql"
)

type ResultResolver struct {
	censys *Censys
}

func NewResultResolver(censys *Censys) *ResultResolver {
	return &ResultResolver{
		censys: censys,
	}
}

func (t *ResultResolver) Resolve(p graphql.ResolveParams) (any, error) {
	ipStr, ok := p.Args["ip"].(string)
	if !ok {
		return nil, fmt.Errorf("`ip` is not provided")
	}

	response, err := t.censys.Find(p.Context, net.ParseIP(ipStr))
	if err != nil {
		return nil, fmt.Errorf("censys error: %w", err)
	}

	result, err := t.censys.Normalize(response)
	if err != nil {
		return nil, fmt.Errorf("censys normalizer: %w", err)
	}

	return result, nil
}
