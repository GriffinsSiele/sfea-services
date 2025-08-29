package model

import (
	"encoding/xml"
	"fmt"
	"time"
)

type DateOnly struct {
	time.Time
}

func NewDateOnly(year int, month time.Month, day int) *DateOnly {
	return &DateOnly{
		Time: time.Date(year, month, day, 0, 0, 0, 0, time.Local),
	}
}

func (t *DateOnly) MarshalXML(e *xml.Encoder, start xml.StartElement) error {
	if err := e.EncodeElement(t.Format(time.DateOnly), start); err != nil {
		return fmt.Errorf("failed to encode timeout in seconds: %w", err)
	}

	return nil
}
