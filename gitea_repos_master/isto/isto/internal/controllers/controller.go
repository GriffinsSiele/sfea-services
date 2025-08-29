package controllers

import (
	"encoding/json"
	"net/http"

	"go.uber.org/zap"
)

type Controller struct{}

func (c *Controller) Error(w http.ResponseWriter, err error, statusCode int, logParams ...zap.Field) {
	zap.L().With(logParams...).Error("request failed", zap.Int("status_code", statusCode), zap.Error(err))

	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(statusCode)

	if err = json.NewEncoder(w).Encode(newErrorResponse(err)); err != nil {
		zap.L().Error("failed to encode error response", zap.Error(err))
	}
}

type errorResponse struct {
	Err string `json:"error"`
}

func newErrorResponse(err error) *errorResponse {
	return &errorResponse{
		Err: err.Error(),
	}
}
