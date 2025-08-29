package extension

import (
	"errors"
	"fmt"
	"io"

	"go.i-sphere.ru/ispherix/pkg/tls/types"
)

type Extension struct {
	Type    types.ExtensionType `json:"type"`
	Content Content             `json:"content,omitempty"`
}

func (e *Extension) Parse(b []byte) error {
	switch e.Type {
	case types.ExtensionTypeEcPointFormats:
		e.Content = &ECPointFormats{}
	case types.ExtensionTypeServerName:
		e.Content = &ServerName{}
	case types.ExtensionTypeSupportedGroups:
		e.Content = &SupportedGroups{}
	}

	if e.Content != nil {
		if err := e.Content.Parse(b); err != nil {
			if !errors.Is(err, io.EOF) {
				return fmt.Errorf("failed to parse extension content: %w", err)
			}
		}
	}

	return nil
}
