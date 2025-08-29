package internal

import (
	"context"
	"encoding/json"
	"errors"
	"fmt"
	"strconv"

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

	ctx = context.WithValue(ctx, normalizer.PhoneContextDefaultRegion, runtime.String("PHONENUMBERS_REGION"))

	httpRequestURL := runtime.URL("D0O_URL")
	httpRequestURL.Path = "/api/ads"

	httpResponse, err := t.http.GET(ctx,
		httpRequestURL,
		client.QueryParam("country", runtime.String("PHONENUMBERS_REGION")),
		client.QueryParam("phone", t.phone.Normalize(ctx, msg.Phone)),
		client.QueryParam("token", runtime.String("D00_TOKEN")),
		client.RandomUserAgent(),
	)

	if err != nil {
		logrus.WithError(err).Errorf("failed to get a response from %s: %v", runtime.URL("D0O_URL"), err)

		return model.NewErrorResponse(err), nil
	}

	//goland:noinspection GoUnhandledErrorResult
	defer httpResponse.Body.Close()

	var httpResponseData response

	if err = json.NewDecoder(httpResponse.Body).Decode(&httpResponseData); err != nil {
		logrus.WithError(err).Errorf("failed to unmarshal the response: %v", err)

		return model.NewErrorResponse(err), nil
	}

	if httpResponseData.Status != responseStatusOK {
		logrus.WithField("http_response_data", httpResponseData).Error("status is not `ok`")

		return model.NewErrorResponse(errors.New("error response")), nil
	}

	var records [][]model.Recorder

	for _, item := range httpResponseData.Data {
		fields, err := t.fields(ctx, item)
		if err != nil {
			return nil, fmt.Errorf("failed to create fields: %w", err)
		}

		if len(fields) > 0 {
			records = append(records, fields)
		}
	}

	return model.NewResponse(records...), nil
}

func (t *Processor) fields(ctx context.Context, item *responseDataItem) ([]model.Recorder, error) {
	var fields []model.Recorder

	if item.Category != nil {
		fields = append(fields, &model.Field[string]{
			Field: "Category",
			Type:  model.FieldTypeString,
			Value: *item.Category,
		})
	}

	if item.Description != nil {
		fields = append(fields, &model.Field[string]{
			Field: "Description",
			Type:  model.FieldTypeString,
			Value: *item.Description,
		})
	}

	if item.Location != nil {
		fields = append(fields, &model.Field[string]{
			Field: "Location",
			Type:  model.FieldTypeString,
			Value: *item.Location,
		})
	}

	if item.Name != nil {
		fields = append(fields, &model.Field[string]{
			Field: "Name",
			Type:  model.FieldTypeString,
			Value: *item.Name,
		})
	}

	if item.Phone != nil {
		fields = append(fields, &model.Field[string]{
			Field: "Phone",
			Type:  model.FieldTypePhone,
			Value: t.phone.ReverseNormalize(ctx, strconv.Itoa(*item.Phone)),
		})
	}

	if item.Price != nil {
		price := *item.Price
		if item.Source != nil && item.Source.Host == "youla.io" {
			price /= 100.0
		}

		fields = append(fields, &model.Field[model.Money]{
			Field: "Price",
			Type:  model.FieldTypeFloat,
			Value: *model.NewMoney(price, 2),
		})
	}

	if item.Source != nil {
		fields = append(fields, &model.Field[string]{
			Field: "Source",
			Type:  model.FieldTypeString,
			Value: item.Source.String(),
		})
	}

	if item.Time != nil {
		fields = append(fields, &model.Field[model.Time]{
			Field: "Time",
			Type:  model.FieldTypeString,
			Value: *item.Time,
		})
	}

	if item.Title != nil {
		fields = append(fields, &model.Field[string]{
			Field: "Title",
			Type:  model.FieldTypeString,
			Value: *item.Title,
		})
	}

	if item.URL != nil {
		fields = append(fields, &model.Field[model.URL]{
			Field: "URL",
			Type:  model.FieldTypeURL,
			Value: *item.URL,
		})
	}

	return fields, nil
}

// ---

type response struct {
	Status string              `json:"status"`
	Data   []*responseDataItem `json:"data"`
}

type responseDataItem struct {
	Category    *string     `json:"category"`
	Description *string     `json:"description"`
	Location    *string     `json:"location"`
	Name        *string     `json:"name"`
	Phone       *int        `json:"phone"`
	Price       *float64    `json:"price"`
	Source      *model.URL  `json:"source"`
	Time        *model.Time `json:"time"`
	Title       *string     `json:"title"`
	URL         *model.URL  `json:"URL"`
}

const responseStatusOK = "ok"
