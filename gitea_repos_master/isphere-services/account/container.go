package main

import (
	"fmt"

	"git.i-sphere.ru/isphere-services/login/command"
	"git.i-sphere.ru/isphere-services/login/console"
	"git.i-sphere.ru/isphere-services/login/contract"
	oauth22 "git.i-sphere.ru/isphere-services/login/controller/oauth2"
	"git.i-sphere.ru/isphere-services/login/controller/security"
	"git.i-sphere.ru/isphere-services/login/oauth2"
	"git.i-sphere.ru/isphere-services/login/util"
	"go.uber.org/dig"
)

func NewContainer() (*dig.Container, error) {
	container := dig.New()

	if err := container.Provide(func() *dig.Container {
		return container
	}); err != nil {
		return nil, fmt.Errorf("failed to self description: %w", err)
	}

	for _, definition := range definitions() {
		if err := container.Provide(definition); err != nil {
			return nil, fmt.Errorf("failed to provide service definition: %w", err)
		}
	}

	if err := container.Provide(func(httpServe *command.HTTPServe) []contract.Commander {
		return []contract.Commander{
			httpServe,
		}
	}); err != nil {
		return nil, fmt.Errorf("failed to provide commander: %w", err)
	}

	if err := container.Provide(func(
		oauth2Authorize *oauth22.Authorize,
		oauth2Token *oauth22.Token,
		securityChallenge *security.Challenge,
		securityDefault *security.Default,
		securityLogin *security.Login,
		securityLogout *security.Logout,
	) []contract.Controller {
		return []contract.Controller{
			oauth2Authorize,
			oauth2Token,
			securityChallenge,
			securityDefault,
			securityLogin,
			securityLogout,
		}
	}); err != nil {
		return nil, fmt.Errorf("failed to provide controller: %w", err)
	}

	return container, nil
}

func definitions() []any {
	return []any{
		command.NewHTTPServe,
		console.NewApp,
		NewApp,
		NewKernel,
		NewRouter,
		oauth2.NewClientStore,
		oauth2.NewJWTAccessGenerate,
		oauth2.NewManager,
		oauth2.NewServer,
		oauth2.NewServerConfig,
		oauth22.NewAuthorize,
		oauth22.NewToken,
		security.NewChallenge,
		security.NewDefault,
		security.NewLogin,
		security.NewLogout,
		util.NewUI,
	}
}
