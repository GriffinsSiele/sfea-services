package model

import (
	"encoding/json"
	"fmt"
	"net/url"
	"strings"
)

type URL struct {
	json.Marshaler
	url.URL
}

func (t *URL) MarshalJSON() ([]byte, error) {
	serialized, err := json.Marshal(t.URL.String())
	if err != nil {
		return nil, fmt.Errorf("failed to marshal URL: %w", err)
	}

	return serialized, nil
}

func (t *URL) UnmarshalJSON(data []byte) error {
	dataString := string(data)
	if dataString == "null" {
		return nil
	}

	parsed, err := url.Parse(strings.Trim(string(data), `"`))
	if err != nil {
		return fmt.Errorf("cannot parse URL value: %w", err)
	}

	t.URL = *parsed

	return nil
}
