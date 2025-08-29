package server

import (
	"context"
	"encoding/json"
	"log/slog"
	"net"
	"net/http"
	"strings"
	"sync"
	"time"

	"github.com/davecgh/go-spew/spew"
	"gopkg.in/yaml.v2"
	"i-sphere.ru/healthcheck/internal/cli"
	"i-sphere.ru/healthcheck/internal/client"
	"i-sphere.ru/healthcheck/internal/env"
	"i-sphere.ru/healthcheck/internal/prom"
	"i-sphere.ru/healthcheck/internal/storage"
)

type HTTPServer struct {
	direct *client.Direct
	memory *storage.Memory
	params *env.Params
	rw     sync.RWMutex
}

func NewHTTPServer(direct *client.Direct, memory *storage.Memory, params *env.Params) *HTTPServer {
	return &HTTPServer{
		direct: direct,
		memory: memory,
		params: params,
	}
}

func (s *HTTPServer) RunAsMaster(ctx context.Context) error {
	return s.Run(context.WithValue(ctx, cli.MasterMode, true))
}

func (s *HTTPServer) Run(ctx context.Context) error {
	srv := http.Server{
		Addr:    s.params.HTTPServeAddr,
		Handler: s,
		ConnContext: func(cc context.Context, c net.Conn) context.Context {
			return context.WithValue(cc, cli.MasterMode, ctx.Value(cli.MasterMode))
		},
	}

	go func() {
		slog.With("addr", srv.Addr).InfoContext(ctx, "starting server")
		if err := srv.ListenAndServe(); err != nil {
			slog.With("error", err).ErrorContext(ctx, "failed to start server")
		}
	}()

	go func() {
		<-ctx.Done()
		//goland:noinspection GoUnhandledErrorResult
		srv.Shutdown(ctx)
	}()

	return nil
}

func (s *HTTPServer) ServeHTTP(w http.ResponseWriter, r *http.Request) {
	switch r.URL.Path {
	case "/metrics":
		s.metrics(w, r)
	default:
		s.notFound(w, r)
	}
}

func (s *HTTPServer) metrics(w http.ResponseWriter, r *http.Request) {
	s.rw.Lock() // one request at a time
	defer s.rw.Unlock()

	res := make([]*storage.MemoryLog, 0)

	inspections := s.memory.Load()
	for _, inspection := range inspections {
		var requestID string
		if inspection.RequestID != nil {
			requestID = inspection.RequestID.String()
		}
		res = append(res, &storage.MemoryLog{
			ID:        requestID,
			Name:      inspection.Name,
			NodeName:  inspection.NodeName,
			Hostname:  inspection.Hostname,
			Events:    inspection.Events,
			Error:     inspection.Error,
			Duration:  inspection.Duration,
			CreatedAt: inspection.CreatedAt,
		})
	}

	if m := r.Context().Value(cli.MasterMode); m != nil {
		if v, ok := m.(bool); ok && v {
			ptrs, err := s.direct.LookupPTR("healthcheck-master-node-worker.isphere-services.svc.cluster.local")
			if err != nil {
				w.WriteHeader(http.StatusInternalServerError)
				//goland:noinspection GoUnhandledErrorResult
				w.Write([]byte(err.Error()))
				return
			}

			ch := make(chan string)
			done := make(chan bool)
			var wg sync.WaitGroup
			wg.Add(len(ptrs))

			go func() {
				for ptr := range ch {
					spew.Dump(ptr)
				}
				close(done)
			}()

			for _, ptr := range ptrs {
				go func(ptr *client.PTR) {
					defer wg.Done()

					ctx, cancel := context.WithTimeout(r.Context(), 1*time.Second)
					defer cancel()

					req, err := http.NewRequestWithContext(ctx, http.MethodGet, "http://"+ptr.String()+":8000/metrics", http.NoBody)
					if err != nil {
						slog.With("error", err).ErrorContext(ctx, "failed to create request")
						return
					}

					req.Header.Set("Accept", "application/json")

					resp, err := client.NewClientWithParams(s.params).Do(req)
					if err != nil {
						slog.With("error", err).ErrorContext(ctx, "failed to get response")
						return
					}
					//goland:noinspection GoUnhandledErrorResult
					defer resp.Body.Close()

					var items []*storage.MemoryLog
					if err := json.NewDecoder(resp.Body).Decode(&items); err != nil {
						slog.With("error", err).ErrorContext(ctx, "failed to decode response")
						return
					}

					for _, item := range items {
						res = append(res, item)
					}
				}(ptr)
			}

			go func() {
				wg.Wait()
				close(ch)
			}()

			<-done
		}
	}

	accept := r.Header.Get("Accept")
	switch {
	case strings.HasPrefix(accept, "application/json"):
		w.Header().Set("Content-Type", "application/json")

		if err := json.NewEncoder(w).Encode(res); err != nil {
			w.WriteHeader(http.StatusInternalServerError)
			//goland:noinspection GoUnhandledErrorResult
			w.Write([]byte(err.Error()))
		}

	case strings.HasPrefix(accept, "application/yaml"):
		w.Header().Set("Content-Type", "application/yaml")

		if err := yaml.NewEncoder(w).Encode(res); err != nil {
			w.WriteHeader(http.StatusInternalServerError)
			//goland:noinspection GoUnhandledErrorResult
			w.Write([]byte(err.Error()))
		}

	case strings.HasPrefix(accept, "text/plain"):
		fallthrough

	default:
		w.Header().Set("Content-Type", "text/plain")

		resBytes, err := (&prom.Marshaller{}).Marshal(res)
		if err != nil {
			w.WriteHeader(http.StatusInternalServerError)
			//goland:noinspection GoUnhandledErrorResult
			w.Write([]byte(err.Error()))
		} else {
			//goland:noinspection GoUnhandledErrorResult
			w.Write(resBytes)
		}
	}
}

func (s *HTTPServer) notFound(w http.ResponseWriter, _ *http.Request) {
	w.WriteHeader(http.StatusNotFound)
	//goland:noinspection GoUnhandledErrorResult
	w.Write([]byte("not found"))
}
