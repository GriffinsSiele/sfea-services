package internal

import (
	"errors"
	"fmt"

	"github.com/graphql-go/graphql"
	"github.com/nyaruka/phonenumbers"
)

type NamesResolver struct {
	d0o *D0O
}

func NewNamesResolver(d0o *D0O) *NamesResolver {
	return &NamesResolver{
		d0o: d0o,
	}
}

func (t *NamesResolver) Resolve(p graphql.ResolveParams) (any, error) {
	phoneNumber, ok := p.Args["phone"].(*phonenumbers.PhoneNumber)
	if !ok {
		return nil, errors.New("cannot cast phone")
	}

	names, err := t.d0o.FindNames(p.Context, phoneNumber)
	if err != nil {
		return nil, fmt.Errorf("failed to find names: %w", err)
	}

	return names.Data, nil
}
