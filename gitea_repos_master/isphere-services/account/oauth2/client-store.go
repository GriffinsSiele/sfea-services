package oauth2

import (
	"fmt"

	"github.com/go-oauth2/oauth2/v4/models"
	"github.com/go-oauth2/oauth2/v4/store"
	"github.com/sirupsen/logrus"
)

func NewClientStore() (*store.ClientStore, error) {
	clients := []*models.Client{
		{
			ID:     "ac8d88f2-a591-4e9b-a079-d06c7c60790e",
			Secret: "cffbdd3a-fb6b-4cee-b611-c2b1fb269522",
			Domain: "http://localhost",
		},
	}

	clientStore := store.NewClientStore()

	for _, client := range clients {
		if err := clientStore.Set(client.ID, client); err != nil {
			return nil, fmt.Errorf("cannot set client: %w", err)
		}

		logrus.WithField("client_id", client.ID).Debug("client added")
	}

	return clientStore, nil
}
