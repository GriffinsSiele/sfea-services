package storage

import (
	"context"
	"encoding/json"
	"fmt"
	"git.i-sphere.ru/isphere-go-modules/framework/pkg/model"
	"git.i-sphere.ru/isphere-go-modules/framework/pkg/runtime"
	"git.i-sphere.ru/isphere-go-modules/framework/pkg/util"
	"github.com/jackc/puddle/v2"
	"github.com/redis/go-redis/v9"
	"github.com/sirupsen/logrus"
	"strings"
	"time"
)

type KeyDB struct {
	pool *KeyDBPool
}

func NewKeyDB() (*KeyDB, error) {
	pool, err := NewKeyDBPool()
	if err != nil {
		return nil, fmt.Errorf("failed to create keyDB pool: %w", err)
	}

	return &KeyDB{
		pool: pool,
	}, nil
}

func (t *KeyDB) Pool() *KeyDBPool {
	return t.pool
}

// ---

type KeyDBPool struct {
	*puddle.Pool[*KeyDBConnection]
}

func NewKeyDBPool() (*KeyDBPool, error) {
	connectionStrings := strings.Split(runtime.String("KEYDB_ADDR"), ",")
	pool, err := puddle.NewPool(&puddle.Config[*KeyDBConnection]{
		Constructor: func(ctx context.Context) (res *KeyDBConnection, err error) {
			return &KeyDBConnection{
				Client: redis.NewClient(&redis.Options{
					Addr:     util.RandomSlice(connectionStrings),
					Password: runtime.String("KEYDB_PASSWORD"),
					DB:       runtime.Int("KEYDB_DATABASE"),
				}),
				scope: runtime.String("KEYDB_KEY"),
			}, nil
		},

		Destructor: func(res *KeyDBConnection) {
			if err := res.Close(); err != nil {
				logrus.WithError(err).Error("failed to close connection")
			}
		},

		MaxSize: int32(10),
	})

	if err != nil {
		return nil, fmt.Errorf("failed to create pool connection: %w", err)
	}

	return &KeyDBPool{pool}, nil
}

func (t *KeyDBPool) Acquire(ctx context.Context) (*KeyDBConnection, error) {
	for {
		res, err := t.Pool.Acquire(ctx)
		if err != nil {
			return nil, fmt.Errorf("failed to acquire internal pool: %w", err)
		}

		connection := res.Value()

		if res.IdleDuration() > time.Second {
			if err := connection.Conn().Ping(ctx); err != nil {
				res.Destroy()

				continue
			}
		}

		return connection, nil
	}
}

// ---

type KeyDBConnection struct {
	*redis.Client
	scope string
}

func (t *KeyDBConnection) Release() {}

func (t *KeyDBConnection) Exists(ctx context.Context, key string) (bool, error) {
	exists, err := t.HExists(ctx, t.scope, key).Result()
	if err != nil {
		return false, fmt.Errorf("failed to check object exists: %w", err)
	}

	return exists, nil
}

func (t *KeyDBConnection) Find(ctx context.Context, key string) (*model.Response, error) {
	serialized, err := t.HGet(ctx, t.scope, key).Result()
	if err != nil {
		return nil, fmt.Errorf("failed to get object: %w", err)
	}

	var object model.Response
	if err = json.Unmarshal([]byte(serialized), &object); err != nil {
		return nil, fmt.Errorf("failed to deserialize object: %w", err)
	}

	if ttl, err := t.Do(ctx, "ttl", t.scope, key).Int64(); err == nil {
		duration := time.Duration(ttl) * time.Second
		object.TTL = &duration
	} else {
		logrus.WithError(err).Error("failed to get object TTL")
	}

	return &object, nil
}

func (t *KeyDBConnection) Persist(ctx context.Context, key string, object *model.Response) (bool, error) {
	serialized, err := json.Marshal(object)
	if err != nil {
		return false, fmt.Errorf("failed to serialize object: %w", err)
	}

	if _, err = t.HSet(ctx, t.scope, key, string(serialized)).Result(); err != nil {
		return false, fmt.Errorf("failed to save object: %w", err)
	}

	if object.TTL == nil {
		return true, nil
	}

	if _, err = t.Do(ctx, "expiremember", t.scope, key, int(object.TTL.Seconds()), "s").Bool(); err != nil {
		logrus.WithError(err).Error("failed to set object TTL")
	}

	return true, nil
}

func (t *KeyDBConnection) Remove(ctx context.Context, key string) (bool, error) {
	if _, err := t.HDel(ctx, t.scope, key).Result(); err != nil {
		return false, fmt.Errorf("failed to delete object: %w", err)
	}

	return true, nil
}
