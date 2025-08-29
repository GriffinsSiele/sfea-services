package handler

import (
	"encoding/json"
	"fmt"
	"net/http"

	"i-sphere.ru/nginx-auth/internal/x"
)

func MetadataHandler(respWriter http.ResponseWriter, req *http.Request, clientHello *x.TLSPlaintext, countryCode *string) {
	m := &metadata{
		RemoteAddr:    x.ClientAddr(req),
		RequestMethod: req.Method,
		RequestProto:  req.Proto,
		HTTPUserAgent: x.Ptr(req.UserAgent()),
		CountryCode:   countryCode,
	}

	if clientHello != nil {
		m.TLS = clientHello.JsonStruct()
	}

	respWriter.Header().Set("Content-Type", "application/json")
	respWriter.WriteHeader(http.StatusOK)

	enc := json.NewEncoder(respWriter)
	enc.SetIndent("", "  ")

	if err := enc.Encode(m); err != nil {
		x.Problem(respWriter, req, fmt.Errorf("failed to serialize metadata: %w", err), http.StatusInternalServerError)
	}
}

type metadata struct {
	RemoteAddr    string         `json:"remote_addr"`
	RequestMethod string         `json:"request_method"`
	RequestProto  string         `json:"request_proto"`
	HTTPUserAgent *string        `json:"http_user_agent"`
	TLS           map[string]any `json:"tls"`
	CountryCode   *string        `json:"country_code"`
}
