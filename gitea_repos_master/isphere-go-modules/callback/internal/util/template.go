package util

import (
	"bytes"
	"encoding/base64"
	"encoding/json"
	"errors"
	"fmt"
	htmltemplate "html/template"
	"net/http"
	"os"
	"strings"
	"text/template"
	"time"

	"github.com/charmbracelet/log"
	"gopkg.in/yaml.v2"
)

func NewTemplate() *template.Template {
	return template.New("").Funcs(template.FuncMap{
		"Base64Decode": base64decode,
		"Hasura":       hasura,
		"Now":          now,
		"Quote":        quote,
		"ToJSON":       toJSON,
		"ToYAML":       toYAML,
		"Unix":         unix,
	})
}

func base64decode(v string) (string, error) {
	v = strings.ReplaceAll(v, ".", "=")
	res, err := base64.StdEncoding.DecodeString(v)
	if err != nil {
		return "", nil
	}

	return string(res), nil
}

func hasura(scope, key string) (string, error) {
	// language=GraphQL
	query := `
query($scope: String!, $key: String!) {
  kv(where: {
    _and: [
      {
        scope: {_eq: $scope}
      }, 
      {
        key: {_eq: $key}
      }
    ]
  }, order_by: [
    {created_at: desc}
  ], limit: 1) {
    value
  }
}

`
	data := map[string]any{
		"query": query,
		"variables": map[string]any{
			"scope": scope,
			"key":   key,
		},
	}

	serialized, err := json.Marshal(data)
	if err != nil {
		return "", fmt.Errorf("failed to marshal captured data: %w", err)
	}

	req, err := http.NewRequest(http.MethodPost, os.Getenv("HASURA_ENDPOINT"), bytes.NewReader(serialized))
	if err != nil {
		return "", fmt.Errorf("failed to create http request: %w", err)
	}

	req.Header.Set("X-Hasura-Access-Key", os.Getenv("HASURA_TOKEN"))

	resp, err := http.DefaultClient.Do(req)
	if err != nil {
		return "", fmt.Errorf("failed to save key/value: %w", err)
	}
	//goland:noinspection GoUnhandledErrorResult
	defer resp.Body.Close()

	if resp.StatusCode != http.StatusOK {
		return "", errors.New("unexpected status code")
	}

	var res hasuraResp
	if err := json.NewDecoder(resp.Body).Decode(&res); err != nil {
		return "", fmt.Errorf("failed to unmarshal body: %w", err)
	}

	for _, kv := range res.Data.KV {
		return kv.Value, nil
	}

	return "", fmt.Errorf("nothing results found")
}

type hasuraResp struct {
	Data struct {
		KV []struct {
			Value string `json:"value"`
		} `json:"kv"`
	} `json:"data"`
}

func now() time.Time {
	return time.Now()
}

func quote(v any) htmltemplate.JS {
	if vStr, ok := v.(string); ok {
		vStr = strings.TrimSpace(vStr)

		if vStr != "" {
			// unquote slashes
			v = strings.ReplaceAll(vStr, "\\", "")
		}
	}

	serialized, err := json.Marshal(v)
	if err != nil {
		log.With("err", err).Errorf("marshal quoted value: %v", v)
	}

	return htmltemplate.JS(serialized)
}

func toJSON(v any) (htmltemplate.JS, error) {
	serialized, err := json.Marshal(v)
	if err != nil {
		return htmltemplate.JS(""), fmt.Errorf("failed to json marshal obj: %w", err)
	}

	return htmltemplate.JS(string(serialized)), nil
}

func toYAML(v any) (string, error) {
	serialized, err := yaml.Marshal(v)
	if err != nil {
		return "", fmt.Errorf("failed to yaml marshal obj: %w", err)
	}

	return string(serialized), nil
}

func unix(v time.Time) int64 {
	return v.Unix()
}
