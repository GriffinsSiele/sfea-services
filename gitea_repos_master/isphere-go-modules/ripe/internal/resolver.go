package internal

import (
	"encoding/json"
	"errors"
	"fmt"
	"io"
	"net"
	"net/http"
	"net/url"
	"reflect"
	"time"

	"git.i-sphere.ru/isphere-go-modules/ripe/internal/model"
	"git.i-sphere.ru/isphere-go-modules/ripe/internal/union"
	"github.com/graphql-go/graphql"
	"github.com/mitchellh/mapstructure"
	"github.com/sirupsen/logrus"
)

func Resolver(p graphql.ResolveParams) (any, error) {
	ipStr, ok := p.Args["ip"].(string)
	if !ok {
		return nil, errors.New("failed to resolve ip")
	}

	ip := net.ParseIP(ipStr)
	if ip == nil {
		return nil, errors.New("failed to parse ip")
	}

	reqURL := &url.URL{
		Scheme: "http", Host: "rest.db.ripe.net", Path: "/search.json",
		RawQuery: url.Values{
			"query-string": []string{ip.String()},
		}.Encode(),
	}

	req, err := http.NewRequest(http.MethodGet, reqURL.String(), http.NoBody)
	if err != nil {
		return nil, fmt.Errorf("failed to make http request: %v", err)
	}

	logrus.WithFields(logrus.Fields{
		"request_body":    nil,
		"request_headers": req.Header,
		"request_id":      p.Context.Value("x-request-id"),
		"request_method":  req.Method,
		"request_proto":   req.Proto,
		"request_time":    time.Now().Format(time.RFC3339),
		"request_uri":     req.RequestURI,
	}).Debug("provider request")

	res, err := http.DefaultClient.Do(req)
	if err != nil {
		return nil, fmt.Errorf("failed to send http request: %v", err)
	}

	content, err := io.ReadAll(res.Body)
	if err != nil {
		return nil, fmt.Errorf("failed to read response body: %w", err)
	}

	//goland:noinspection GoUnhandledErrorResult
	defer res.Body.Close()

	logrus.WithFields(logrus.Fields{
		"request_id":       p.Context.Value("x-request-id"),
		"response_body":    string(content),
		"response_headers": res.Header,
		"response_status":  res.StatusCode,
		"response_time":    time.Now().Format(time.RFC3339),
	}).Debug("provider response")

	var response model.Response
	if err = json.Unmarshal(content, &response); err != nil {
		return nil, fmt.Errorf("failed to unmarshal response: %v", err)
	}

	results := make([]union.Item, 0, len(response.Objects.Object))

	for _, object := range response.Objects.Object {
		var item union.Item

		switch object.Type {
		case string(union.ItemTypeInetNum):
			item = new(union.InetNum)

		case string(union.ItemTypeOrganisation):
			item = new(union.Organisation)

		case string(union.ItemTypeRole):
			item = new(union.Role)

		case string(union.ItemTypeRoute):
			item = new(union.Route)

		default:
			logrus.WithField("type", object.Type).Errorf("unsupported object type")

			continue
		}

		tmp := map[string]any{}
		for _, attr := range object.Attributes.Attribute {
			if exists, ok := tmp[attr.Name]; ok {
				attr.Value = fmt.Sprintf("%s, %s", exists, attr.Value)
			}

			tmp[attr.Name] = attr.Value
		}

		if err = decode(tmp, &item); err != nil {
			return nil, fmt.Errorf("failed to unmap structure: %v", err)
		}

		results = append(results, item)
	}

	return results, nil
}

func decode(input map[string]any, result any) error {
	decoder, err := mapstructure.NewDecoder(&mapstructure.DecoderConfig{
		Metadata:   nil,
		DecodeHook: mapstructure.ComposeDecodeHookFunc(timeHook()),
		Result:     result,
	})

	if err != nil {
		return fmt.Errorf("failed to create decoder: %w", err)
	}

	if err = decoder.Decode(input); err != nil {
		return fmt.Errorf("failed to decode with hook: %w", err)
	}

	return nil
}

func timeHook() mapstructure.DecodeHookFunc {
	return func(f, t reflect.Type, data any) (any, error) {
		if t != reflect.TypeOf(new(time.Time)) {
			return data, nil
		}

		switch f.Kind() {
		case reflect.String:
			if vTime, err := time.Parse(time.RFC3339, data.(string)); err != nil {
				return nil, fmt.Errorf("failed to parse time: %w", err)
			} else {
				return vTime, nil
			}

		default:
			return data, nil
		}
	}
}
