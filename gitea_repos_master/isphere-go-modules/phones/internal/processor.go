package internal

import (
	"context"
	"encoding/json"
	"errors"
	"fmt"

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

	httpRequestURL := runtime.URL("D0O_URL")
	httpRequestURL.Path = "/api/adjacent"

	httpResponse, err := t.http.GET(ctx,
		httpRequestURL,
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

	var httpResponseData struct {
		Status string   `json:"status"`
		Data   []string `json:"data"`
	}

	if err := json.NewDecoder(httpResponse.Body).Decode(&httpResponseData); err != nil {
		logrus.WithError(err).Errorf("failed to unmarshal the response: %v", err)

		return model.NewErrorResponse(err), nil
	}

	if httpResponseData.Status != "ok" {
		logrus.WithField("http_response_data", httpResponseData).Error("status is not `ok`")

		return model.NewErrorResponse(errors.New("error response")), nil
	}

	var records [][]model.Recorder
	for _, phone := range httpResponseData.Data {
		records = append(records, []model.Recorder{
			&model.Field[string]{
				Field: "Phone",
				Type:  model.FieldTypePhone,
				Value: t.phone.ReverseNormalize(ctx, phone),
			},
		})
	}

	return model.NewResponse(records...), nil
}
