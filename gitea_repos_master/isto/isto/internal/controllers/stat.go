package controllers

import (
	"net/http"
	"os"
	"strconv"

	"i-sphere.ru/isto/internal/contracts"
	"i-sphere.ru/isto/internal/middlewares"
)

type Stat struct {
	*Controller
}

func NewStat() *Stat {
	return new(Stat)
}

func (s *Stat) ConfigureRoutes(router contracts.Router) {
	router.HandleFunc("GET /stats/requests", s.GETRequests)
	router.HandleFunc("GET /stats/total-requests", s.GETTotalRequests)
}

func (s *Stat) GETRequests(w http.ResponseWriter, _ *http.Request) {
	_, _ = w.Write([]byte(strconv.FormatInt(middlewares.GlobalCounter.Load(), 10)))
}

func (s *Stat) GETTotalRequests(w http.ResponseWriter, _ *http.Request) {
	w.Header().Add("X-Node-Name", os.Getenv("NODE_NAME"))
	_, _ = w.Write([]byte(strconv.FormatUint(middlewares.TotalCounter.Load(), 10)))
}
