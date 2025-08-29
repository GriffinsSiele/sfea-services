package internal

import (
	"errors"
	"fmt"

	"github.com/graphql-go/graphql"
	"github.com/nyaruka/phonenumbers"
)

type AdjacentResolver struct {
	d0o *D0O
}

func NewAdjacentResolver(d0o *D0O) *AdjacentResolver {
	return &AdjacentResolver{
		d0o: d0o,
	}
}

func (t *AdjacentResolver) Resolve(p graphql.ResolveParams) (any, error) {
	phoneNumber, ok := p.Args["phone"].(*phonenumbers.PhoneNumber)
	if !ok {
		return nil, errors.New("cannot cast phone")
	}

	adjacent, err := t.d0o.FindAdjacent(p.Context, phoneNumber)
	if err != nil {
		return nil, fmt.Errorf("failed to find adjacent: %w", err)
	}

	return adjacent.Data, nil
}
