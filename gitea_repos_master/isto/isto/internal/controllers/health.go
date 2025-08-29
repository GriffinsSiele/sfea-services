package controllers

import (
	"context"
	"fmt"
	"net/http"

	"github.com/jackc/puddle/v2"
	"go.mongodb.org/mongo-driver/bson"
	"go.mongodb.org/mongo-driver/mongo"
	"go.mongodb.org/mongo-driver/mongo/options"

	"i-sphere.ru/isto/internal/contracts"
	"i-sphere.ru/isto/internal/databases"
)

type Health struct {
	*Controller
	mongo *databases.Mongo
}

func NewHealth(mongo *databases.Mongo) *Health {
	return &Health{
		mongo: mongo,
	}
}

func (h *Health) ConfigureRoutes(router contracts.Router) {
	router.HandleFunc("GET /health", h.GET)
}

func (h *Health) GET(w http.ResponseWriter, r *http.Request) {
	conn, err := h.mongo.Acquire(r.Context())
	if err != nil {
		h.Error(w, fmt.Errorf("failed to acquire mongo connection: %w", err), http.StatusInternalServerError)
		return
	}
	defer conn.Release()

	if err = conn.Value().Ping(r.Context(), nil); err != nil {
		h.Error(w, fmt.Errorf("failed to ping mongo: %w", err), http.StatusInternalServerError)
		return
	}

	if err = h.checkUniqueIndexAndCreateItIfNotExists(r.Context(), conn); err != nil {
		h.Error(w, fmt.Errorf("failed to check unique index: %w", err), http.StatusInternalServerError)
		return
	}
}

func (h *Health) checkUniqueIndexAndCreateItIfNotExists(ctx context.Context, conn *puddle.Resource[*mongo.Client]) error {
	db := conn.Value().Database("files")

	collectionNames, err := db.ListCollectionNames(ctx, bson.M{})
	if err != nil {
		return fmt.Errorf("failed to list collections: %w", err)
	}

	const uniqIndexName = "request_id_uniq"

	for _, collectionName := range collectionNames {
		collection := db.Collection(collectionName)

		indexes, err := collection.Indexes().ListSpecifications(ctx)
		if err != nil {
			return fmt.Errorf("failed to list indexes: %w", err)
		}

		var uniqIndexFound bool
		for _, index := range indexes {
			if index.Name == uniqIndexName {
				uniqIndexFound = true
				break
			}
		}
		if !uniqIndexFound {
			if _, err = collection.Indexes().CreateOne(ctx, mongo.IndexModel{
				Keys: bson.M{"request_id": 1},
				Options: options.Index().
					SetName(uniqIndexName).
					SetUnique(true),
			}); err != nil {
				return fmt.Errorf("failed to create unique index: %w", err)
			}
		}
	}

	return nil
}
