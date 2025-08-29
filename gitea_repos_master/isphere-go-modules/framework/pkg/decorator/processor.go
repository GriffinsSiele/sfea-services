package decorator

import (
	"context"
	"errors"
	"fmt"
	"git.i-sphere.ru/isphere-go-modules/framework/pkg/contract"
	"git.i-sphere.ru/isphere-go-modules/framework/pkg/model"
	"git.i-sphere.ru/isphere-go-modules/framework/pkg/runtime"
	"git.i-sphere.ru/isphere-go-modules/framework/pkg/storage"
	"time"
)

type Processor struct {
	keyDB     *storage.KeyDB
	processor contract.Processor
}

func NewProcessor(keyDB *storage.KeyDB, processor contract.Processor) *Processor {
	return &Processor{
		keyDB:     keyDB,
		processor: processor,
	}
}

func (t *Processor) Invoke(ctx context.Context, message contract.Message) (*model.Response, error) {
	var (
		connection *storage.KeyDBConnection
		err        error
	)

	if ctx.Value(contract.CacheControlNoCache) == nil || ctx.Value(contract.CacheControlNoStore) == nil {
		if connection, err = t.keyDB.Pool().Acquire(ctx); err != nil {
			return nil, fmt.Errorf("failed to acquire keyDB connection: %w", err)
		}

		defer connection.Release()

		found, err := connection.Exists(ctx, message.GetKey())
		if err != nil {
			return nil, fmt.Errorf("cannot check item exists in KeyDB: %w", err)
		}

		if found {
			response, err := connection.Find(ctx, message.GetKey())
			if err != nil {
				return nil, fmt.Errorf("cannot get item from KeyDB: %w", err)
			}

			return response, nil
		}
	}

	if ctx.Value(contract.CacheControlOnlyIfCached) != nil {
		return nil, nil
	}

	response, err := t.processor.Process(ctx, message)
	if err != nil {
		return nil, fmt.Errorf("error in the decorating processor: %w", err)
	}

	if response.IsCacheable() && ctx.Value(contract.CacheControlNoStore) == nil {
		var duration time.Duration

		if maxAge, ok := ctx.Value(contract.CacheControlMaxAge).(int); ok {
			duration = time.Duration(maxAge) * time.Second
		} else {
			duration = runtime.Duration("KEYDB_DEFAULT_TTL")
		}

		response.TTL = &duration

		if ok, err := connection.Persist(ctx, message.GetKey(), response); err != nil {
			return response, fmt.Errorf("failed to update response in KeyDB: %w", err)
		} else if !ok {
			return response, errors.New("unexpected update restrictions in KeyDB")
		}

		response.TTL = &duration
	}

	return response, nil
}
