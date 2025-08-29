package model

import (
	"bytes"
	"encoding/xml"
	"fmt"
	"time"
)

type Date struct {
	Value *time.Time
}

func (t *Date) MarshalJSON() ([]byte, error) {
	buf := bytes.NewBuffer([]byte{})

	if t.Value != nil {
		buf.WriteRune('"')
		buf.WriteString(t.Value.Format(time.DateOnly))
		buf.WriteRune('"')
	} else {
		buf.WriteString("null")
	}

	return buf.Bytes(), nil
}

func (t *Date) UnmarshalXML(d *xml.Decoder, start xml.StartElement) error {
	var v string
	if err := d.DecodeElement(&v, &start); err != nil {
		return fmt.Errorf("failed to decode element: %s", err)
	}

	if v == "" {
		return nil
	}

	parsed, err := time.Parse(time.DateOnly, v)
	if err != nil {
		return fmt.Errorf("failed to parse date: %w", err)
	}

	*t = Date{Value: &parsed}

	return nil
}
