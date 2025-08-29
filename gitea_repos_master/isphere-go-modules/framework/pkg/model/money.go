package model

import (
	"encoding/json"
	"fmt"
)

type Money struct {
	Value     float64
	Precision uint8
}

func NewMoney(value float64, precision uint8) *Money {
	return &Money{
		Value:     value,
		Precision: precision,
	}
}

func (t *Money) MarshalJSON() ([]byte, error) {
	representative := fmt.Sprintf(fmt.Sprintf("%%.%df", t.Precision), t.Value)

	serialized, err := json.Marshal(representative)
	if err != nil {
		return nil, fmt.Errorf("failed to marshal money: %w", err)
	}

	return serialized, nil
}
