package connection

import (
	"context"
	"fmt"
	"os"

	"github.com/jackc/pgx/v5/pgxpool"
)

type Postgres struct {
	connection *pgxpool.Pool
}

func NewPostgres() (*Postgres, error) {
	connection, err := pgxpool.New(
		context.Background(),
		fmt.Sprintf(
			"host=%s port=%d user=%s password=%s dbname=%s sslmode=disable",
			os.Getenv("POSTGRES_HOST"),
			5432,
			os.Getenv("POSTGRES_USER"),
			os.Getenv("POSTGRES_PASSWORD"),
			os.Getenv("POSTGRES_DATABASE"),
		),
	)
	if err != nil {
		return nil, fmt.Errorf("failed to connect to postgres: %w", err)
	}
	if err := connection.Ping(context.Background()); err != nil {
		return nil, fmt.Errorf("failed to ping postgres: %w", err)
	}

	return &Postgres{
		connection: connection,
	}, nil
}

func (t *Postgres) Acquire(ctx context.Context) (*pgxpool.Conn, error) {
	return t.connection.Acquire(ctx)
}
