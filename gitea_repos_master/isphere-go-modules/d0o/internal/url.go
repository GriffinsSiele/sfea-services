package internal

import (
	"fmt"
	"net/url"
	"strings"
)

type URL struct {
	url.URL
}

func (t *URL) UnmarshalJSON(marshaled []byte) error {
	marshaledString := strings.Trim(string(marshaled), `"`)
	if marshaledString == "null" {
		return nil
	}

	parsed, err := url.Parse(marshaledString)
	if err != nil {
		return fmt.Errorf("failed to parse URL: %w", err)
	}

	t.URL = *parsed

	return nil
}
