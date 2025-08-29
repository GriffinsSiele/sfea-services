package keydb

import (
	"context"
	"encoding/json"
	"fmt"
	"math"
	"time"

	"gitea-http.gitea.svc.cluster.local/isphere-go-modules/coordinator/internal/config"
	"gitea-http.gitea.svc.cluster.local/isphere-go-modules/coordinator/internal/contract"
	"gitea-http.gitea.svc.cluster.local/isphere-go-modules/coordinator/internal/model"
	"gitea-http.gitea.svc.cluster.local/isphere-go-modules/coordinator/internal/util"
	"github.com/redis/go-redis/v9"
)

type Conn struct {
	*redis.Client
}

func (c *Conn) Release() {}

func (c *Conn) Exists(ctx context.Context, scope, key string) (bool, error) {
	exists, err := c.HExists(ctx, scope, key).Result()
	if err != nil {
		return false, fmt.Errorf("failed to check object exists: %w", err)
	}

	return exists, nil
}

func (c *Conn) Find(ctx context.Context, scope, key string, object any) (any, error) {
	serialized, err := c.HGet(ctx, scope, key).Result()
	if err != nil {
		return nil, fmt.Errorf("failed to get object: %w", err)
	}

	if err = json.Unmarshal([]byte(serialized), &object); err != nil {
		return nil, fmt.Errorf("failed to deserialize object: %w", err)
	}

	return object, nil
}

func (c *Conn) TTL(ctx context.Context, scope, key string) (*time.Duration, error) {
	ttl, err := c.Do(ctx, "ttl", scope, key).Int64()
	if err != nil {
		return nil, fmt.Errorf("failed to get TTL: %w", err)
	}

	return util.Ptr(time.Duration(ttl) * time.Second), nil
}

func (c *Conn) EasyPersist(
	ctx context.Context,
	key string,
	response *model.Response,
	checkType *config.CheckType,
) error {
	if !checkType.Upstream.KeyDB.Enabled || ctx.Value(contract.CacheControlNoStore) != nil {
		return nil
	}

	var duration *time.Duration

	if response.IsFailed() {
		duration = checkType.Upstream.KeyDB.GetTTLFailed()
	} else {
		if maxAge, ok := ctx.Value(contract.CacheControlMaxAge).(int); ok {
			duration = util.Ptr(time.Duration(maxAge) * time.Second)
		} else {
			duration = checkType.Upstream.KeyDB.GetTTL()
		}
	}

	if _, err := c.Persist(ctx, checkType.Upstream.KeyDB.Scope, key, response, duration); err != nil {
		return fmt.Errorf("failed save to keydb: %w", err)
	}

	timestamp := time.Unix(response.Timestamp, 0)
	response.Metadata.TTL = &model.ResponseMetadataTTL{
		Age:          util.Ptr(int(math.Round(time.Since(timestamp).Seconds()))),
		LastModified: util.Ptr(timestamp),
		ETag:         util.Ptr(key),
		Expires:      util.Ptr(timestamp.Add(util.PtrVal(duration))),
	}

	return nil
}

func (c *Conn) Persist(ctx context.Context, scope, key string, object any, ttl *time.Duration) (bool, error) {
	serialized, err := json.Marshal(object)
	if err != nil {
		return false, fmt.Errorf("failed to serialize object: %w", err)
	}

	if _, err = c.HSet(ctx, scope, key, string(serialized)).Result(); err != nil {
		return false, fmt.Errorf("failed to set object: %w", err)
	}

	if ttl == nil {
		return true, nil
	}

	if _, err = c.Do(ctx, "expiremember", scope, key, int(ttl.Seconds()), "s").Bool(); err != nil {
		return false, fmt.Errorf("failed to set TTL: %w", err)
	}

	return true, nil
}

func (c *Conn) Remove(ctx context.Context, scope, key string) (bool, error) {
	if _, err := c.HDel(ctx, scope, key).Result(); err != nil {
		return false, fmt.Errorf("failed to remove object: %w", err)
	}

	return true, nil
}
