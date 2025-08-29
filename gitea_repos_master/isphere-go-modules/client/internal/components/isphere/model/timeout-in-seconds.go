package model

import (
	"encoding/xml"
	"fmt"
	"time"
)

type TimeoutInSeconds struct {
	time.Duration
}

func NewTimeoutInSeconds(value int, unit time.Duration) TimeoutInSeconds {
	return TimeoutInSeconds{
		Duration: time.Duration(value) * unit,
	}
}

func (t TimeoutInSeconds) MarshalXML(e *xml.Encoder, start xml.StartElement) error {
	if err := e.EncodeElement(t.Seconds(), start); err != nil {
		return fmt.Errorf("failed to encode timeout in seconds: %w", err)
	}

	return nil
}
