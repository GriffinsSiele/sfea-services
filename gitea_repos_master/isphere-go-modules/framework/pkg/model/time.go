package model

import (
	"fmt"
	"strings"
	"time"

	"github.com/araddon/dateparse"
)

type Time struct {
	time.Time
}

func (t *Time) UnmarshalJSON(data []byte) error {
	dataString := string(data)
	if dataString == "null" {
		return nil
	}

	parsed, err := dateparse.ParseStrict(strings.Trim(dataString, `"`))
	if err != nil {
		return fmt.Errorf("cannot parse time value: %w", err)
	}

	t.Time = parsed

	return nil
}
