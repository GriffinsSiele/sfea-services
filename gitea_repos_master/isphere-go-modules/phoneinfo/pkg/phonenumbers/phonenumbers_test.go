package phonenumbers_test

import (
	"context"
	"testing"

	"git.i-sphere.ru/isphere-go-modules/phone/pkg/phonenumbers"
	"github.com/davecgh/go-spew/spew"
	"github.com/stretchr/testify/assert"
)

func TestRussian(t *testing.T) {
	t.Parallel()

	var number phonenumbers.Phonenumbers

	output, err := number.Parse(context.Background(), "+79772776278", nil)

	assert.NoError(t, err)
	spew.Dump(output)
}

func TestUkrainian(t *testing.T) {
	t.Parallel()

	var number phonenumbers.Phonenumbers

	output, err := number.Parse(context.Background(), "+380965221930", nil)

	assert.NoError(t, err)
	spew.Dump(output)
}

func TestBelorussian(t *testing.T) {
	t.Parallel()

	var number phonenumbers.Phonenumbers

	output, err := number.Parse(context.Background(), "+375336598981", nil)

	assert.NoError(t, err)
	spew.Dump(output)
}
