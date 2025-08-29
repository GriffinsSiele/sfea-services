package model

import (
	"encoding/json"
	"testing"
	"time"

	"github.com/stretchr/testify/assert"
)

func TestTime(t *testing.T) {
	t.Parallel()

	input := []byte(`{"time": "2021-09-20 05:41:01"}`)

	output := struct {
		Time Time `json:"time"`
	}{}

	assert.NoError(t, json.Unmarshal(input, &output))
	assert.Equal(t, "20 Sep 21 05:41 UTC", output.Time.Format(time.RFC822))

	input = []byte(`{"time": "20 Sep 21 05:41 UTC"}`)

	assert.NoError(t, json.Unmarshal(input, &output))
	assert.Equal(t, "20 Sep 21 05:41 UTC", output.Time.Format(time.RFC822))
}
