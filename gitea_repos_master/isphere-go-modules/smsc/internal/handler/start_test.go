package handler

import (
	"testing"

	"github.com/joho/godotenv"
	"github.com/stretchr/testify/assert"
)

func TestEscapeCapture(t *testing.T) {
	t.Parallel()

	_ = godotenv.Load("../../.env")
	_ = godotenv.Load("../../.env.local")

	key := "+79001112233"

	assert.NoError(t, escape("test", key))

	captured, err := capture("test")

	assert.NoError(t, err)
	assert.Equal(t, captured, key)
}
