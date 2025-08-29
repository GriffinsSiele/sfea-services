package internal

import (
	"encoding/json"
	"net/http"
)

type AbstractController struct {
}

func (c *AbstractController) error(w http.ResponseWriter, err error, statusCode int) {
	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(statusCode)
	//goland:noinspection GoUnhandledErrorResult
	json.NewEncoder(w).Encode(map[string]string{"error": err.Error()})
}
