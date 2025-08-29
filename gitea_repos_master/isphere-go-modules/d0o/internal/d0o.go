package internal

import (
	"context"
	"fmt"
	"net/url"
	"strings"

	"github.com/nyaruka/phonenumbers"
)

type D0O struct {
	client *Client
}

func NewD0O(client *Client) *D0O {
	return &D0O{
		client: client,
	}
}

func (t *D0O) FindAds(ctx context.Context, phoneNumber *phonenumbers.PhoneNumber, phoneRegion string) (*Ads, error) {
	var (
		ads   Ads
		phone = phonenumbers.Format(phoneNumber, phonenumbers.E164)
	)

	phoneRegion = strings.ToLower(phoneRegion)

	if err := t.client.GET(ctx, "/api/ads", url.Values{"country": {phoneRegion}, "phone": {phone}}, &ads); err != nil {
		return nil, fmt.Errorf("failed to execute ads response: %w", err)
	}

	if ads.Status != AdsStatusOK {
		return nil, ErrUnexpectedResponse()
	}

	return &ads, nil
}

func (t *D0O) FindAdjacent(ctx context.Context, phoneNumber *phonenumbers.PhoneNumber) (*Adjacent, error) {
	var (
		adjacent Adjacent
		phone    = phonenumbers.Format(phoneNumber, phonenumbers.E164)
	)

	if err := t.client.GET(ctx, "/api/adjacent", url.Values{"phone": {phone}}, &adjacent); err != nil {
		return nil, fmt.Errorf("failed to execute adjacent response: %w", err)
	}

	if adjacent.Status != AdjacentStatusOK {
		return nil, ErrUnexpectedResponse()
	}

	return &adjacent, nil
}

func (t *D0O) FindNames(ctx context.Context, phoneNumber *phonenumbers.PhoneNumber) (*Names, error) {
	var (
		names Names
		phone = phonenumbers.Format(phoneNumber, phonenumbers.E164)
	)

	if err := t.client.GET(ctx, "/api/numbuster", url.Values{"phone": {phone}}, &names); err != nil {
		return nil, fmt.Errorf("failed to execute numbuster response: %w", err)
	}

	if names.Status != NamesStatusOK {
		return nil, ErrUnexpectedResponse()
	}

	return &names, nil
}
