package oauth2

import (
	"github.com/go-oauth2/oauth2/v4/generates"
	"github.com/go-oauth2/oauth2/v4/manage"
	"github.com/go-oauth2/oauth2/v4/store"
)

func NewManager(
	clientStore *store.ClientStore,
	jwtAccessGenerate *generates.JWTAccessGenerate,
) *manage.Manager {
	manager := manage.NewDefaultManager()

	manager.SetAuthorizeCodeTokenCfg(manage.DefaultAuthorizeCodeTokenCfg)

	manager.MustTokenStorage(store.NewMemoryTokenStore())

	manager.MapAccessGenerate(jwtAccessGenerate)

	manager.MapClientStorage(clientStore)

	return manager
}
