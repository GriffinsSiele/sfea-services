package internal

import (
	"fmt"
	"strings"
	"time"
)

type Time struct {
	*time.Time
}

func (t *Time) UnmarshalJSON(marshaled []byte) error {
	marshaledString := strings.Trim(string(marshaled), `"`)
	if marshaledString == "null" {
		return nil
	}

	parsed, err := time.Parse(time.DateTime, marshaledString)
	if err != nil {
		return fmt.Errorf("failed to parse time: %w", err)
	}

	t.Time = &parsed

	return nil
}
