package validator

import (
	"testing"

	"github.com/stretchr/testify/assert"
)

func TestPhoneValidatorFromEnv(t *testing.T) {
	t.Setenv("PHONENUMBERS_REGION", "RU")

	validator, err := NewValidator()

	assert.NoError(t, err)

	obj1 := struct {
		Phone string `validate:"phone=%env(PHONENUMBERS_REGION)%"`
	}{"79001112233"}

	assert.NoError(t, validator.Struct(obj1))
}

func TestPhoneValidatorRU(t *testing.T) {
	t.Parallel()

	validator, err := NewValidator()

	assert.NoError(t, err)

	obj1 := struct {
		Phone string `validate:"phone=RU"`
	}{"79001112233"}

	assert.NoError(t, validator.Struct(obj1))

	obj2 := struct {
		Phone string `validate:"phone=RU"`
	}{"380441112233"}

	assert.Error(t, validator.Struct(obj2))
}

func TestPhoneValidatorUA(t *testing.T) {
	t.Parallel()

	validator, err := NewValidator()

	assert.NoError(t, err)

	obj1 := struct {
		Phone string `validate:"phone=UA"`
	}{"79001112233"}

	assert.Error(t, validator.Struct(obj1))

	obj2 := struct {
		Phone string `validate:"phone=UA"`
	}{"380441112233"}

	assert.NoError(t, validator.Struct(obj2))
}
