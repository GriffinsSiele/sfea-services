package internal

import (
	"fmt"
	"net"

	"github.com/graphql-go/graphql"
)

type ResultResolver struct {
	shodan *Shodan
}

func NewResultResolver(shodan *Shodan) *ResultResolver {
	return &ResultResolver{
		shodan: shodan,
	}
}

func (t *ResultResolver) Resolve(p graphql.ResolveParams) (any, error) {
	ipStr, ok := p.Args["ip"].(string)
	if !ok {
		return nil, fmt.Errorf("`ip` is not provided")
	}

	response, err := t.shodan.Find(p.Context, net.ParseIP(ipStr))
	if err != nil {
		return nil, fmt.Errorf("shodan error: %w", err)
	}

	result, err := t.shodan.Normalize(response)
	if err != nil {
		return nil, fmt.Errorf("shodan normalizer: %w", err)
	}

	return result, nil
}
