package databases

import (
	"testing"

	"github.com/stretchr/testify/assert"
)

func TestMongo(t *testing.T) {
	t.Parallel()

	mongo := new(Mongo)
	assert.Equal(t, "test_12", mongo.GetCollectionNameWithID("test", 123_456_789))
}
