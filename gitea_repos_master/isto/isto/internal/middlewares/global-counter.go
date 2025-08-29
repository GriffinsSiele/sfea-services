package middlewares

import (
	"net/http"
	"sync/atomic"
)

var GlobalCounter atomic.Int64
var TotalCounter atomic.Uint64

func WithGlobalCounter(next http.HandlerFunc) http.HandlerFunc {
	return func(w http.ResponseWriter, r *http.Request) {
		GlobalCounter.Add(1)
		defer GlobalCounter.Add(-1)

		TotalCounter.Add(1)

		next.ServeHTTP(w, r)
	}
}
