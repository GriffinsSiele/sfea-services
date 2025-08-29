package clients

import (
	"context"
	"fmt"
	"net"
	"os"
	"time"

	"github.com/go-redis/redis/v8"
	"go.i-sphere.ru/proxy/pkg/utils"
)

func NewRedis() (*redis.Client, error) {
	client := redis.NewClient(&redis.Options{
		Addr:     net.JoinHostPort(os.Getenv("REDIS_HOST"), os.Getenv("REDIS_PORT")),
		Password: os.Getenv("REDIS_PASSWORD"),
		DB:       utils.MustInt(os.Getenv("REDIS_DATABASE")),
	})

	cancelCtx, cancel := context.WithTimeout(context.Background(), 5*time.Second)
	defer cancel()

	pong, err := client.Ping(cancelCtx).Result()
	if err != nil {
		return nil, fmt.Errorf("failed to ping redis: %w", err)
	}
	if pong != "PONG" {
		return nil, fmt.Errorf("invalid pong response: %s", pong)
	}

	return client, nil
}
