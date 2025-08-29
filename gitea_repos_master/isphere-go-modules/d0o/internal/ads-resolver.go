package internal

import (
	"errors"
	"fmt"

	"github.com/graphql-go/graphql"
	"github.com/nyaruka/phonenumbers"
)

type AdsResolver struct {
	d0o *D0O
}

func NewAdsResolver(d0o *D0O) *AdsResolver {
	return &AdsResolver{
		d0o: d0o,
	}
}

func (t *AdsResolver) Resolve(p graphql.ResolveParams) (any, error) {
	phoneNumber, ok := p.Args["phone"].(*phonenumbers.PhoneNumber)
	if !ok {
		return nil, errors.New("cannot cast phone")
	}

	ads, err := t.d0o.FindAds(p.Context, phoneNumber, p.Args["region"].(string))
	if err != nil {
		return nil, fmt.Errorf("failed to find ads: %w", err)
	}

	return ads.Data, nil
}
