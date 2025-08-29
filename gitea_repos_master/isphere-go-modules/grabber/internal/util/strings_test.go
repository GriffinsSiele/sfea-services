package util_test

import (
	"testing"

	"git.i-sphere.ru/isphere-go-modules/grabber/internal/util"
	"github.com/stretchr/testify/assert"
)

func TestMakeVariants(t *testing.T) {
	t.Parallel()

	variants := util.MakeVariants("иванов (петров) сергей")
	assert.Contains(t, variants, "иванов сергей")
	assert.Contains(t, variants, "петров сергей")

	variants = util.MakeVariants("андреев (дмитриев) алексей (максим)")
	assert.Contains(t, variants, "андреев алексей")
	assert.Contains(t, variants, "дмитриев алексей")
	assert.Contains(t, variants, "андреев максим")
	assert.Contains(t, variants, "дмитриев максим")
}
