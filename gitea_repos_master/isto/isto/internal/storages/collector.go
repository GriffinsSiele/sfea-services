package storages

import (
	"context"
	"fmt"

	"github.com/mitchellh/mapstructure"
	"go.mongodb.org/mongo-driver/bson"
	"go.mongodb.org/mongo-driver/mongo/options"

	"i-sphere.ru/isto/internal/databases"
)

type Collector struct {
	mongo *databases.Mongo
}

func NewCollector(mongo *databases.Mongo) *Collector {
	return &Collector{
		mongo: mongo,
	}
}

func (c *Collector) Load(ctx context.Context, id int64, databaseName, collectionName string, schema any) error {
	conn, err := c.mongo.Acquire(ctx)
	if err != nil {
		return fmt.Errorf("failed to acquire mongo connection: %w", err)
	}
	defer conn.Release()

	var rawResult bson.M
	if err = conn.Value().
		Database(databaseName).
		Collection(c.mongo.GetCollectionNameWithID(collectionName, id)).
		FindOne(
			ctx,
			bson.M{
				"request_id": id,
			},
		).
		Decode(&rawResult); err != nil {
		return fmt.Errorf("failed to load collection: %w", err)
	}

	if err = mapstructure.Decode(rawResult["data"], &schema); err != nil {
		return fmt.Errorf("failed to decode schema: %w", err)
	}

	return nil
}

func (c *Collector) Store(ctx context.Context, id int64, databaseName, collectionName string, data any) error {
	conn, err := c.mongo.Acquire(ctx)
	if err != nil {
		return fmt.Errorf("failed to acquire mongo connection: %w", err)
	}
	defer conn.Release()

	collectionNameWithSuffix := c.mongo.GetCollectionNameWithID(collectionName, id)

	if _, err = conn.Value().
		Database(databaseName).
		Collection(collectionNameWithSuffix).
		UpdateOne(
			ctx,
			bson.M{
				"request_id": id,
			},
			bson.M{
				"$set": bson.M{
					"request_id": id,
					"data":       data,
				},
			},
			options.Update().SetUpsert(true),
		); err != nil {
		return fmt.Errorf("failed to update collection: %w", err)
	}

	return nil
}

func (c *Collector) collectionKey(databaseName, collectionName string) string {
	return databaseName + "." + collectionName
}
