package servers

import (
	"fmt"
	"net/http"

	"go.uber.org/zap"

	"i-sphere.ru/isto/internal/contracts"
)

type Mux struct {
	*http.ServeMux
}

func NewMux(controllers []contracts.Controller) *Mux {
	m := &Mux{
		ServeMux: http.NewServeMux(),
	}

	for _, controller := range controllers {
		zap.L().Debug("registering controller", zap.Any("controller", fmt.Sprintf("%T", controller)))

		controller.ConfigureRoutes(m)
	}

	return m
}
