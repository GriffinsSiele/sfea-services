package server_name

import "go.i-sphere.ru/ispherix/pkg/tls/types"

type Entry struct {
	NameType types.ServerNameType `json:"name_type"`
	Name     string               `json:"name"`
}
