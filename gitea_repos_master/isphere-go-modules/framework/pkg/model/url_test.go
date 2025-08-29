package model

import (
	"encoding/json"
	"testing"

	"github.com/stretchr/testify/assert"
)

func TestURL(t *testing.T) {
	t.Parallel()

	input := []byte(`{"url": "https://yandex.ru/path?param=value"}`)

	output := struct {
		URL URL `json:"url"`
	}{}

	assert.NoError(t, json.Unmarshal(input, &output))
	assert.Equal(t, "https", output.URL.Scheme)
	assert.Equal(t, "yandex.ru", output.URL.Host)
	assert.Equal(t, "/path", output.URL.Path)
	assert.True(t, output.URL.Query().Has("param"))
	assert.Equal(t, "value", output.URL.Query().Get("param"))

	input = []byte(`{"url": "/test#string"}`)

	assert.NoError(t, json.Unmarshal(input, &output))
	assert.Empty(t, output.URL.Scheme)
	assert.Empty(t, output.URL.Host)
	assert.Equal(t, "/test", output.URL.Path)
	assert.Equal(t, "string", output.URL.Fragment)
}
