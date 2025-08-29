package model

import (
	"encoding/json"
	"testing"

	"github.com/stretchr/testify/assert"
)

func TestMoney(t *testing.T) {
	t.Parallel()

	input := NewMoney(1000.123, 2)

	serialized, err := json.Marshal(input)

	assert.NoError(t, err)
	assert.Equal(t, `"1000.12"`, string(serialized))

	input = NewMoney(1, 3)

	serialized, err = json.Marshal(input)
	
	assert.NoError(t, err)
	assert.Equal(t, `"1.000"`, string(serialized))
}
