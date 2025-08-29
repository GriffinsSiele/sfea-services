package databases

import (
	"context"
	"fmt"
	"math"
	"os"
	"strconv"

	"github.com/jackc/puddle/v2"
	"go.mongodb.org/mongo-driver/mongo"
	"go.mongodb.org/mongo-driver/mongo/options"
)

type Mongo struct {
	*puddle.Pool[*mongo.Client]
}

func NewMongo() (*Mongo, error) {
	m := new(Mongo)

	pool, err := puddle.NewPool(&puddle.Config[*mongo.Client]{
		Constructor: func(ctx context.Context) (*mongo.Client, error) {
			client, err := mongo.Connect(
				ctx,
				options.Client().ApplyURI(os.Getenv("MONGO_DSN")),
				options.Client().SetBSONOptions(&options.BSONOptions{
					UseJSONStructTags:   true,
					NilMapAsEmpty:       true,
					NilSliceAsEmpty:     true,
					NilByteSliceAsEmpty: true,
					OmitZeroStruct:      true,
					ZeroMaps:            true,
					ZeroStructs:         true,
				}),
			)
			if err != nil {
				return nil, fmt.Errorf("failed to connect to mongo: %w", err)
			}

			return client, nil
		},

		Destructor: func(client *mongo.Client) {
			//goland:noinspection GoUnhandledErrorResult
			client.Disconnect(context.Background())
		},

		MaxSize: 10,
	})

	if err != nil {
		return nil, fmt.Errorf("failed to create mongo pool: %w", err)
	}

	m.Pool = pool

	return m, nil
}

func (m *Mongo) GetCollectionNameWithID(collectionName string, id int64) string {
	const batchSize = 10_000_000
	const delimiter = "_"

	suffix := strconv.FormatFloat(math.Floor(float64(id)/float64(batchSize)), 'f', 0, 64)
	return collectionName + delimiter + suffix
}
