package main

import (
	"context"
	"testing"
	"time"

	"git.i-sphere.ru/isphere-go-modules/d0o/internal"
	"github.com/nyaruka/phonenumbers"
	"github.com/stretchr/testify/assert"
	"go.uber.org/fx"
	"go.uber.org/fx/fxtest"
)

func TestAdsFound(t *testing.T) {
	t.Parallel()

	opts := append(
		options(),

		fx.Provide(func(d0o *internal.D0O) *internal.Ads {
			phoneNumber, err := phonenumbers.Parse("+79772776278", "RU")

			assert.NoError(t, err)

			response, err := d0o.FindAds(context.TODO(), phoneNumber, "RU")

			assert.NoError(t, err)
			assert.Equal(t, "ok", string(response.Status))
			assert.Greater(t, len(response.Data), 0)

			ad := response.Data[0]

			assert.Equal(t, "Участок 7.8 сот. (СНТ, ДНП)", ad.Title)
			assert.Equal(t, 1485040.0, ad.Price)
			assert.NotNil(t, ad.Time)
			assert.Equal(t, "2020-08-10T17:29:04Z", ad.Time.Format(time.RFC3339))
			assert.NotNil(t, ad.Phone)
			assert.Equal(t, "+79772776278", phonenumbers.Format(ad.Phone.PhoneNumber, phonenumbers.E164))
			assert.Equal(t, "Сергей Коденцов", ad.Name)
			assert.NotEmpty(t, ad.Description)
			assert.Equal(t, "Санкт-Петербург, ", ad.Location)
			assert.Equal(t, "avito.ru", ad.Source)
			assert.Equal(t, "Недвижимость, Земельные участки", ad.Category)
			assert.NotNil(t, ad.URL)

			return response
		}),

		fx.Invoke(func(response *internal.Ads, shutdowner fx.Shutdowner) {
			assert.NoError(t, shutdowner.Shutdown())
		}),
	)

	fxtest.New(t, opts...).Run()
}
