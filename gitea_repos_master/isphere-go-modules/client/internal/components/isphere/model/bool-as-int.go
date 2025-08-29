package model

import (
	"encoding/xml"
	"fmt"
)

type BoolAsInt bool

func (t *BoolAsInt) MarshalXML(e *xml.Encoder, start xml.StartElement) error {
	var numericValue uint8
	if *t {
		numericValue = 1
	}

	if err := e.EncodeElement(numericValue, start); err != nil {
		return fmt.Errorf("failed to encode bool as int: %w", err)
	}

	return nil
}
