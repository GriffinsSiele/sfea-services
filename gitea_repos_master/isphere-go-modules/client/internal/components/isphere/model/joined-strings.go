package model

import (
	"encoding/xml"
	"fmt"
	"strings"
)

type JoinedStrings []string

func (t JoinedStrings) MarshalXML(e *xml.Encoder, start xml.StartElement) error {
	if err := e.EncodeElement(strings.Join(t, ","), start); err != nil {
		return fmt.Errorf("failed to encode sources: %w", err)
	}

	return nil
}
