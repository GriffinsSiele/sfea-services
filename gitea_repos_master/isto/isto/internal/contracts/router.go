package contracts

import "net/http"

type Router interface {
	HandleFunc(string, func(http.ResponseWriter, *http.Request))
}
