package servers

import (
	"net/http"
	"os"
)

type HTTP struct {
	*http.Server
}

func NewHTTP(mux *Mux) *HTTP {
	return &HTTP{
		Server: &http.Server{
			Addr:    os.Getenv("HTTP_SERVER_ADDR"),
			Handler: mux,
		},
	}
}
