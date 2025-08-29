package internal

import (
	"context"
	"encoding/json"
	"errors"
	"fmt"
	"net/url"
	"strconv"
	"strings"

	"git.i-sphere.ru/isphere-go-modules/framework/pkg/client"
	"git.i-sphere.ru/isphere-go-modules/framework/pkg/contract"
	"git.i-sphere.ru/isphere-go-modules/framework/pkg/model"
	"git.i-sphere.ru/isphere-go-modules/framework/pkg/normalizer"
	"git.i-sphere.ru/isphere-go-modules/framework/pkg/runtime"
	"github.com/sirupsen/logrus"
)

type Processor struct {
	http  *client.HTTP
	phone *normalizer.Phone
}

func NewProcessor(http *client.HTTP, phone *normalizer.Phone) *Processor {
	return &Processor{
		http:  http,
		phone: phone,
	}
}

func (t *Processor) Process(ctx context.Context, message contract.Message) (*model.Response, error) {
	msg, ok := message.(*Message)
	if !ok {
		return nil, fmt.Errorf("cannot process the message as internal message")
	}

	ctx = context.WithValue(ctx, normalizer.PhoneContextDefaultRegion, "RU")

	httpRequestURL := runtime.URL("CENSYS_URL")
	httpRequestURL.Path = "/api/v2/hosts/" + msg.IP
	httpRequestURL.User = url.UserPassword(
		runtime.String("CENSYS_HTTP_BASIC_USER"),
		runtime.String("CENSYS_HTTP_BASIC_PASSWORD"),
	)

	httpResponse, err := t.http.GET(ctx, httpRequestURL, client.RandomUserAgent())

	if err != nil {
		logrus.WithError(err).Errorf("failed to get a response from %s: %v", runtime.URL("D0O_URL"), err)

		return model.NewErrorResponse(err), nil
	}

	//goland:noinspection GoUnhandledErrorResult
	defer httpResponse.Body.Close()

	var httpResponseData responseData

	if err := json.NewDecoder(httpResponse.Body).Decode(&httpResponseData); err != nil {
		logrus.WithError(err).Errorf("failed to unmarshal the response: %v", err)

		return model.NewErrorResponse(err), nil
	}

	if httpResponseData.Status != "OK" {
		logrus.WithField("http_response_data", httpResponseData).Error("status is not `OK`")

		return model.NewErrorResponse(errors.New("error response")), nil
	}

	var (
		records [][]model.Recorder
		record  []model.Recorder
	)

	if result := httpResponseData.Result; result != nil {
		if result.IP != nil {
			record = append(record, &model.Field[string]{
				Field: "ip",
				Type:  model.FieldTypeString,
				Value: *result.IP,
			})

			record = append(record, &model.Field[string]{
				Field: "recordtype",
				Type:  model.FieldTypeString,
				Value: "ip",
			})
		}

		if location := result.Location; location != nil {
			if location.Country != nil {
				record = append(record, &model.Field[string]{
					Field: "country",
					Type:  model.FieldTypeString,
					Value: *location.Country,
				})
			}

			if location.CountryCode != nil {
				record = append(record, &model.Field[string]{
					Field: "country_code",
					Type:  model.FieldTypeString,
					Value: *location.CountryCode,
				})
			}

			if location.Province != nil {
				record = append(record, &model.Field[string]{
					Field: "province",
					Type:  model.FieldTypeString,
					Value: *location.Province,
				})
			}

			if location.City != nil {
				record = append(record, &model.Field[string]{
					Field: "city",
					Type:  model.FieldTypeString,
					Value: *location.City,
				})
			}

			if location.Timezone != nil {
				record = append(record, &model.Field[string]{
					Field: "timezone",
					Type:  model.FieldTypeString,
					Value: *location.Timezone,
				})
			}

			if coordinates := location.Coordinates; coordinates != nil {
				record = append(record, &model.Field[*model.Location]{
					Field: "Location",
					Type:  model.FieldTypeMap,
					Value: t.location(&httpResponseData),
				})
			}
		}

		if autonomousSystem := result.AutonomousSystem; autonomousSystem != nil {
			if autonomousSystem.ASN != nil {
				record = append(record, &model.Field[string]{
					Field: "asn",
					Type:  model.FieldTypeString,
					Value: strconv.Itoa(*autonomousSystem.ASN),
				})
			}

			if autonomousSystem.Name != nil {
				record = append(record, &model.Field[string]{
					Field: "organization",
					Type:  model.FieldTypeString,
					Value: *autonomousSystem.Name,
				})
			}
		}

		for _, service := range result.Services {
			serviceRecord := []model.Recorder{
				&model.Field[string]{
					Field: "recordtype",
					Type:  model.FieldTypeString,
					Value: "service",
				},
			}

			if service.Port != nil {
				serviceRecord = append(serviceRecord, &model.Field[string]{
					Field: "port",
					Type:  model.FieldTypeString,
					Value: strconv.Itoa(*service.Port),
				})
			}

			if service.ServiceName != nil {
				serviceRecord = append(serviceRecord, &model.Field[string]{
					Field: "service",
					Type:  model.FieldTypeString,
					Value: *service.ServiceName,
				})
			}

			if service.TransportProtocol != nil {
				serviceRecord = append(serviceRecord, &model.Field[string]{
					Field: "transport",
					Type:  model.FieldTypeString,
					Value: *service.TransportProtocol,
				})
			}

			records = append(records, serviceRecord)
		}
	}

	records = append([][]model.Recorder{record}, records...)

	return model.NewResponse(records...), nil
}

func (t *Processor) location(data *responseData) *model.Location {
	return &model.Location{
		Coords: []float64{
			data.Result.Location.Coordinates.Latitude,
			data.Result.Location.Coordinates.Longitude,
		},
		Text: t.locationText(data),
	}
}

func (t *Processor) locationText(data *responseData) string {
	components := make([]string, 0, 3)

	if data.Result.Location.Country != nil {
		components = append(components, *data.Result.Location.CountryCode)
	}

	if data.Result.Location.Province != nil {
		components = append(components, *data.Result.Location.Province)
	}

	if data.Result.Location.City != nil {
		components = append(components, *data.Result.Location.City)
	}

	return strings.Join(components, ", ")
}

// ---

type responseData struct {
	Code   int    `json:"code"`
	Status string `json:"status"`
	Result *struct {
		IP       *string `json:"ip"`
		Services []*struct {
			Port              *int    `json:"port"`
			ServiceName       *string `json:"service_name"`
			TransportProtocol *string `json:"transport_protocol"`
		} `json:"services"`
		Location *struct {
			Continent   *string `json:"continent"`
			Country     *string `json:"country"`
			CountryCode *string `json:"country_code"`
			City        *string `json:"city"`
			PostalCode  *string `json:"postal_code"`
			Timezone    *string `json:"timezone"`
			Province    *string `json:"province"`
			Coordinates *struct {
				Latitude  float64 `json:"latitude"`
				Longitude float64 `json:"longitude"`
			} `json:"coordinates"`
		} `json:"location"`
		LocationUpdatedAt *model.Time `json:"location_updated_at"`
		AutonomousSystem  *struct {
			ASN         *int    `json:"asn"`
			Description *string `json:"description"`
			BGPPrefix   *string `json:"bgp_prefix"`
			Name        *string `json:"name"`
			CountryCode *string `json:"country_code"`
		} `json:"autonomous_system"`
		AutonomousSystemUpdatedAt *model.Time `json:"autonomous_system_updated_at"`
	} `json:"result"`
}
