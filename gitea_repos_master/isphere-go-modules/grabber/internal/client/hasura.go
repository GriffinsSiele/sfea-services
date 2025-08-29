package client

import (
	"context"
	"fmt"
	"net/http"

	"github.com/hasura/go-graphql-client"
)

func NewHasura(addr, adminSecret string) *graphql.Client {
	return graphql.NewClient(addr, &HasuraHandler{
		adminSecret: adminSecret,
	})
}

type HasuraHandler struct {
	adminSecret string
}

func (t *HasuraHandler) Do(req *http.Request) (*http.Response, error) {
	if t.adminSecret != "" {
		req.Header.Add("X-Hasura-Admin-Secret", t.adminSecret)
	}

	return http.DefaultClient.Do(req)
}

// ---

type HasuraRepository struct {
	hasura *graphql.Client
}

func NewHasuraRepository(hasura *graphql.Client) *HasuraRepository {
	return &HasuraRepository{
		hasura: hasura,
	}
}

func (t *HasuraRepository) FindRegions(ctx context.Context) (*Regions, error) {
	var regions Regions

	if err := t.hasura.Exec(ctx,
		// language=GraphQL
		`{
    regions_regions {
        code
        names
    }
}
`, &regions, nil); err != nil {
		return nil, fmt.Errorf("failed to fetch dictionary regions: %w", err)
	}

	return &regions, nil
}

// ---

type Regions struct {
	DictionariesRegions []*DictionaryRegion `graphql:"regions_regions"`
}

type DictionaryRegion struct {
	Code  string   `graphql:"code"`
	Names []string `graphql:"names"`
}
