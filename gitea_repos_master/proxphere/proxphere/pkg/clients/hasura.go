package clients

import (
	"bytes"
	"context"
	"encoding/json"
	"os"

	http "github.com/Danny-Dasilva/fhttp"
	"github.com/pkg/errors"
	"go.i-sphere.ru/proxy/pkg/utils"
)

type Hasura struct {
}

func NewHasura() *Hasura {
	return &Hasura{}
}

func (h *Hasura) InjectHeadersByUsernameAndPassword(ctx context.Context, r *http.Request, username, password string) error {
	reqData := map[string]any{
		"query": `query ($username: String!, $password_hash: String!){
    proxy_specs(where: {
        username: {_eq: $username},
        password_hash: {_eq: $password_hash},
    }) {
        proxy_spec_options {
            name
            value
        }
    }
}
`,
		"variables": map[string]string{
			"username":      username,
			"password_hash": utils.MD5Hash(password),
		},
	}
	reqBody, err := json.Marshal(reqData)
	if err != nil {
		return errors.Wrap(err, "failed to marshal request body")
	}

	req, err := http.NewRequestWithContext(ctx, http.MethodPost, os.Getenv("HASURA_ENDPOINT"), bytes.NewReader(reqBody))
	if err != nil {
		return errors.Wrap(err, "failed to create request")
	}
	req.Header.Set("X-Hasura-Admin-Secret", os.Getenv("HASURA_ACCESS_TOKEN"))

	resp, err := http.DefaultClient.Do(req)
	if err != nil {
		return errors.Wrap(err, "failed to send request")
	}
	//goland:noinspection GoUnhandledErrorResult
	defer resp.Body.Close()

	var response proxySpecsDataWrapper
	if err = json.NewDecoder(resp.Body).Decode(&response); err != nil {
		return errors.Wrap(err, "failed to decode response body")
	}
	if len(response.Data.ProxySpecs) == 0 {
		return errors.New("proxy not found")
	}
	if len(response.Data.ProxySpecs) > 1 {
		return errors.New("more than one proxy found")
	}

	for _, option := range response.Data.ProxySpecs[0].Options {
		r.Header.Add(option.Name, option.Value)
	}

	return nil
}

type proxySpecsDataWrapper struct {
	Data struct {
		ProxySpecs []struct {
			Options []struct {
				Name  string `json:"name"`
				Value string `json:"value"`
			} `json:"proxy_spec_options"`
		} `json:"proxy_specs"`
	} `json:"data"`
}
