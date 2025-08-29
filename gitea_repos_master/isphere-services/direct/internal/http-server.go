package internal

import "net/http"

func NewHTTPServer() *http.Server {
	return &http.Server{
		Addr:    ":8000",
		Handler: new(HTTPHandler),
	}
}
