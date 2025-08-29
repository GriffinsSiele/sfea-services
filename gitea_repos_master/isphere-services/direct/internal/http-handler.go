package internal

import (
	"fmt"
	"net/http"
	"sync"
)

type HTTPHandler struct{}

func (h *HTTPHandler) ServeHTTP(w http.ResponseWriter, r *http.Request) {
	switch r.URL.Path {
	case "/.well-known/names":
		h.handleNames(w, r)
	default:
		w.WriteHeader(http.StatusNotFound)
	}
}

func (h *HTTPHandler) handleNames(w http.ResponseWriter, _ *http.Request) {
	w.Header().Set("Content-Type", "text/plain")

	var wg sync.WaitGroup
	ch := make(chan fmt.Stringer)
	done := make(chan bool)
	go func() {
		for line := range ch {
			//goland:noinspection GoUnhandledErrorResult
			fmt.Fprintln(w, line)
		}
		close(done)
	}()

	wg.Add(1)
	go func() {
		defer wg.Done()
		if pl := HostnameToPodLink.Load(); pl != nil {
			for _, p := range *pl {
				ch <- p.ARec()
			}
		}
	}()

	wg.Add(1)
	go func() {
		defer wg.Done()
		if sl := HostnameToServiceLink.Load(); sl != nil {
			for _, s := range *sl {
				ch <- s.ARec()
				for _, p := range s.Pods {
					ch <- p.PTRLink(&s)
				}
			}
		}
	}()

	go func() {
		wg.Wait()
		close(ch)
	}()

	<-done
}
