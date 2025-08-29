package storage

import (
	"context"
	"git.i-sphere.ru/isphere-go-modules/framework/pkg/model"
	"git.i-sphere.ru/isphere-go-modules/framework/pkg/util"
	"github.com/stretchr/testify/assert"
	"testing"
	"time"
)

func TestKeyDB(t *testing.T) {
	//t.Skip("test can only be run locally with the specified environment")

	t.Parallel()

	// working subject
	ctx := context.Background()
	objectKey := util.RandomString(16)

	keyDB, err := NewKeyDB()
	assert.NoError(t, err)

	// check exists
	connection, err := keyDB.Pool().Acquire(ctx)
	assert.NoError(t, err)

	found, err := connection.Exists(ctx, objectKey)
	assert.NoError(t, err)
	assert.False(t, found)

	connection.Release()

	// check create new item
	duration := 5 * time.Second
	object := &model.Response{
		Message: "test item",
		TTL:     &duration,
	}

	connection, err = keyDB.Pool().Acquire(ctx)
	assert.NoError(t, err)

	created, err := connection.Persist(ctx, objectKey, object)
	assert.NoError(t, err)
	assert.True(t, created)

	connection.Release()

	// find exists item
	connection, err = keyDB.Pool().Acquire(ctx)
	assert.NoError(t, err)

	result, err := connection.Find(ctx, objectKey)
	assert.NoError(t, err)
	assert.NotNil(t, result.TTL)
	assert.Equal(t, object, result)

	connection.Release()

	// remove item
	connection, err = keyDB.Pool().Acquire(ctx)
	assert.NoError(t, err)

	removed, err := connection.Remove(ctx, objectKey)
	assert.NoError(t, err)
	assert.True(t, removed)

	found, err = connection.Exists(ctx, objectKey)
	assert.NoError(t, err)
	assert.False(t, found)

	connection.Release()
}
