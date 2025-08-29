package normalizer

import (
	"context"

	"git.i-sphere.ru/isphere-go-modules/framework/pkg/contract"
	"github.com/nyaruka/phonenumbers"
	"github.com/sirupsen/logrus"
)

type PhoneContextValues string

const (
	PhoneContextDefaultRegion PhoneContextValues = "phone-context-default-region"
)

const PhoneDefaultRegionFallback = "RU"

type Phone struct {
	contract.Normalizer[string]
}

func NewPhone() *Phone {
	return &Phone{}
}

func (t *Phone) Normalize(ctx context.Context, v string) string {
	defaultRegion, ok := ctx.Value(PhoneContextDefaultRegion).(string)

	if !ok {
		logrus.Warnf("phone normalizer using fallback default region, please set it corretly")

		defaultRegion = PhoneDefaultRegionFallback
	}

	phoneNumber, err := phonenumbers.Parse(v, defaultRegion)

	if err != nil {
		logrus.WithError(err).Errorf("cannot parse phone number: %v", err)

		return ""
	}

	return phonenumbers.Format(phoneNumber, phonenumbers.E164)
}

func (t *Phone) ReverseNormalize(ctx context.Context, v string) string {
	return t.Normalize(ctx, v)
}
