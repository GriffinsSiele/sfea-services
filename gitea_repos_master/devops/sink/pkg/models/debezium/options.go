package debezium

import (
	"encoding/json"
	"strings"
)

type Options struct {
	Name   string         `json:"name"`
	Config map[string]any `json:"config"`
}

const MySqlConnectorClass string = "io.debezium.connector.mysql.MySqlConnector"

type PlainList []string

func (p PlainList) MarshalJSON() ([]byte, error) {
	return json.Marshal(strings.Join(p, ","))
}
