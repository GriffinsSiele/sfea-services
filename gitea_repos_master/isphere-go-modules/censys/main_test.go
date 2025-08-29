package main

import (
	"context"
	"net"
	"testing"

	"git.i-sphere.ru/isphere-go-modules/censys/internal"
	"github.com/stretchr/testify/assert"
	"go.uber.org/fx"
	"go.uber.org/fx/fxtest"
)

func TestFoundKnown(t *testing.T) {
	t.Parallel()

	opts := append(
		options(),

		fx.Provide(func(censys *internal.Censys) *internal.CensysResponse {
			response, err := censys.Find(context.TODO(), net.ParseIP("78.140.221.69"))

			assert.NoError(t, err)
			assert.Equal(t, 200, response.Code)
			assert.Equal(t, "OK", string(response.Status))

			result := response.Result

			assert.NotNil(t, result)
			assert.Equal(t, "78.140.221.69", result.IP)

			services := result.Services

			assert.NotNil(t, services)
			assert.Len(t, services, 3)

			var countOfSuccess int

			for _, service := range services {
				switch service.Port {
				case 22:
					assert.Equal(t, "SSH", service.ServiceName)
					assert.Equal(t, "TCP", service.TransportProtocol)

					countOfSuccess++

				case 80, 443:
					assert.Equal(t, "HTTP", service.ServiceName)
					assert.Equal(t, "TCP", service.TransportProtocol)

					countOfSuccess++

				default:
					assert.Failf(t, "unexpected service port", "unexpected service port: %d", service.Port)
				}
			}

			assert.Equal(t, 3, countOfSuccess)

			location := result.Location

			assert.NotNil(t, location)
			assert.Equal(t, "Europe", location.Continent)
			assert.Equal(t, "Russia", location.Country)
			assert.Equal(t, "RU", location.CountryCode)
			assert.Equal(t, "Moscow", location.City)
			assert.Equal(t, "101000", location.PostalCode)
			assert.Equal(t, "Europe/Moscow", location.Timezone)
			assert.Equal(t, "Moscow", location.Province)

			coordinates := location.Coordinates

			assert.NotNil(t, coordinates)
			assert.Equal(t, 55.75222, coordinates.Latitude)
			assert.Equal(t, 37.61556, coordinates.Longitude)

			assert.NotNil(t, result.LocationUpdatedAt)

			autonomousSystem := result.AutonomousSystem

			assert.NotNil(t, autonomousSystem)
			assert.Equal(t, 48096, autonomousSystem.ASN)
			assert.Equal(t, "ITGRAD", autonomousSystem.Description)
			assert.Equal(t, "78.140.221.0/24", autonomousSystem.BGPPrefix)
			assert.Equal(t, "ITGRAD", autonomousSystem.Name)
			assert.Equal(t, "RU", autonomousSystem.CountryCode)

			assert.NotNil(t, result.AutonomousSystemUpdatedAt)

			return response
		}),

		fx.Provide(func(censys *internal.Censys, response *internal.CensysResponse) []internal.Result {
			results, err := censys.Normalize(response)

			assert.NoError(t, err)
			assert.Len(t, results, 4)

			var countOfSuccess int

			for _, result := range results {
				switch result.Type() {
				case internal.ResultTypeIP, internal.ResultTypeService:
					countOfSuccess++

				default:
					assert.Failf(t, "unexpected result type", "unexpected result type: %v", result.Type())
				}
			}

			assert.Equal(t, 4, countOfSuccess)

			return results
		}),

		fx.Invoke(func(response *internal.CensysResponse, results []internal.Result, shutdowner fx.Shutdowner) {
			assert.NoError(t, shutdowner.Shutdown())
		}),
	)

	fxtest.New(t, opts...).Run()
}

func TestNotFound(t *testing.T) {
	t.Parallel()

	opts := append(
		options(),

		fx.Provide(func(censys *internal.Censys) *internal.CensysResponse {
			response, err := censys.Find(context.TODO(), net.ParseIP("127.0.0.1"))

			assert.NoError(t, err)

			return response
		}),

		fx.Provide(func(censys *internal.Censys, response *internal.CensysResponse) []internal.Result {
			results, err := censys.Normalize(response)

			assert.NoError(t, err)
			assert.Len(t, results, 0)

			return results
		}),

		fx.Invoke(func(response *internal.CensysResponse, results []internal.Result, shutdowner fx.Shutdowner) {
			assert.NoError(t, shutdowner.Shutdown())
		}),
	)

	fxtest.New(t, opts...).Run()
}
