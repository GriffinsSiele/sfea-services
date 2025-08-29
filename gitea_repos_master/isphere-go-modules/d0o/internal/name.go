package internal

import (
	"encoding/json"
	"fmt"
	"strconv"
)

type Name struct {
	Surname string    `json:"lastName"`
	Name    string    `json:"firstName"`
	Count   StringInt `json:"count"`
}

type StringInt int

func (t *StringInt) UnmarshalJSON(b []byte) error {
	var item any
	if err := json.Unmarshal(b, &item); err != nil {
		return fmt.Errorf("failed to unmarshal StringInt: %w", err)
	}

	switch v := item.(type) {
	case int:
		*t = StringInt(v)
	case float64:
		*t = StringInt(int(v))
	case string:
		i, err := strconv.Atoi(v)
		if err != nil {
			return fmt.Errorf("failed to cast StringInt as int: %w", err)
		}

		*t = StringInt(i)
	}

	return nil
}
