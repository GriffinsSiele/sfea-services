package controllers

import (
	"context"
	"encoding/xml"
	"fmt"
	"net/http"
	"strconv"

	"go.uber.org/zap"

	"i-sphere.ru/isto/internal/contracts"
	"i-sphere.ru/isto/internal/middlewares"
	"i-sphere.ru/isto/internal/storages"
	requestSchema "i-sphere.ru/isto/pkg/models/main-service/request/schema"
	responseSchema "i-sphere.ru/isto/pkg/models/main-service/response/schema"
)

type File struct {
	*Controller
	collector *storages.Collector
}

func NewFile(collector *storages.Collector) *File {
	return &File{
		collector: collector,
	}
}

func (f *File) ConfigureRoutes(router contracts.Router) {
	router.HandleFunc("GET /api/v1/files/{collection}/{id}", middlewares.WithGlobalCounter(f.GET))
	router.HandleFunc("PUT /api/v1/files/{collection}/{id}", middlewares.WithGlobalCounter(f.PUT))
}

func (f *File) GET(w http.ResponseWriter, r *http.Request) {
	schema, id, err := f.resolveParams(r)
	if err != nil {
		f.Error(w, fmt.Errorf("failed to resolve params: %w", err), http.StatusBadRequest)
		return
	}

	logParams := []zap.Field{
		zap.Int64("id", id),
	}

	if err = f.collector.Load(r.Context(), id, "files", r.PathValue("collection"), schema); err != nil {
		f.Error(w, fmt.Errorf("failed to load schema: %w", err), http.StatusNotFound, logParams...)
		return
	}

	w.Header().Set("Content-Type", "application/xml")
	if err = xml.NewEncoder(w).Encode(schema); err != nil {
		f.Error(w, fmt.Errorf("failed to marshal schema: %w", err), http.StatusUnprocessableEntity, logParams...)
		return
	}
}

func (f *File) PUT(w http.ResponseWriter, r *http.Request) {
	schema, id, err := f.resolveParams(r)
	if err != nil {
		f.Error(w, fmt.Errorf("failed to resolve params: %w", err), http.StatusBadRequest)
		return
	}

	if err = xml.NewDecoder(r.Body).Decode(schema); err != nil {
		f.Error(w, fmt.Errorf("failed to unmarshal schema: %w", err), http.StatusUnprocessableEntity, zap.Int64("id", id))
		return
	}

	w.WriteHeader(http.StatusAccepted)

	go func(id int64, collectionName string, schema any) {
		if err = f.collector.Store(context.Background(), id, "files", collectionName, schema); err != nil {
			zap.L().Error("failed to store", zap.Int64("id", id), zap.Error(err))
		}
	}(id, r.PathValue("collection"), schema)
}

func (f *File) resolveParams(r *http.Request) (any, int64, error) {
	var schema any
	switch r.PathValue("collection") {
	case requestsTypeValue:
		schema = new(requestSchema.Request)
	case responsesTypeValue:
		schema = new(responseSchema.Response)
	default:
		return nil, 0, fmt.Errorf("unknown collection: %s", r.PathValue("collection"))
	}

	id, err := strconv.ParseInt(r.PathValue("id"), 10, 64)
	if err != nil {
		return nil, 0, fmt.Errorf("failed to parse id: %w", err)
	}

	return schema, id, nil
}

type collectionValue = string

const (
	requestsTypeValue  collectionValue = "requests"
	responsesTypeValue collectionValue = "responses"
)
