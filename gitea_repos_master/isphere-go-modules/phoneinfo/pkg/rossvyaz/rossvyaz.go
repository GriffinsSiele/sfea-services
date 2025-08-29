package rossvyaz

import (
	"bytes"
	"context"
	"encoding/json"
	"errors"
	"fmt"
	"net/http"
	"os"
)

type Rossvyaz struct {
	endpoint    string
	adminSecret string
}

func NewRossvyaz() *Rossvyaz {
	return &Rossvyaz{
		endpoint:    os.Getenv("HASURA_ENDPOINT"),
		adminSecret: os.Getenv("HASURA_ADMIN_SECRET"),
	}
}

func (r *Rossvyaz) FindOneByPhone(ctx context.Context, phone string) (*Result, error) {
	var regionCode, partOfNumber int
	if _, err := fmt.Sscanf(phone, "+7%3d%7d", &regionCode, &partOfNumber); err != nil {
		return nil, fmt.Errorf("invalid phone number: %w", err)
	}

	// language=graphql
	requestQuery := `query ($regionCode: Int!, $number: Int!) {
    rossvyaz_rossvyaz(where: {
        abcdef: {_eq: $regionCode},
        phone_poolstart: {_lte: $number},
        phone_poolend: {_gte: $number},
    }, limit: 2) {
		operator
        regions
		regioncode
    }
}
`
	requestData := &RequestData{
		Query: requestQuery,
		Variables: map[string]any{
			"regionCode": regionCode,
			"number":     partOfNumber,
		},
	}

	request := bytes.NewBuffer([]byte{})
	if err := json.NewEncoder(request).Encode(requestData); err != nil {
		return nil, fmt.Errorf("failed to encode request: %w", err)
	}

	req, err := http.NewRequestWithContext(ctx, http.MethodPost, r.endpoint, request)
	if err != nil {
		return nil, fmt.Errorf("failed to create request: %w", err)
	}

	if r.adminSecret != "" {
		req.Header.Set("X-Hasura-Admin-Secret", r.adminSecret)
	}

	resp, err := http.DefaultClient.Do(req)
	if err != nil {
		return nil, fmt.Errorf("failed to do request: %w", err)
	}
	//goland:noinspection GoUnhandledErrorResult
	defer resp.Body.Close()

	var response ResponseData
	if err = json.NewDecoder(resp.Body).Decode(&response); err != nil {
		return nil, fmt.Errorf("failed to decode response: %w", err)
	}

	for _, result := range response.Data.Collection {
		return result, nil
	}
	return nil, errors.New("no result found")
}

type RequestData struct {
	Query     string         `json:"query"`
	Variables map[string]any `json:"variables"`
}

type ResponseData struct {
	Data struct {
		Collection []*Result `json:"rossvyaz_rossvyaz"`
	} `json:"data"`
}

type Result struct {
	Operator   string   `json:"operator"`
	Regions    []string `json:"regions"`
	RegionCode int      `json:"regioncode,string"`
}
