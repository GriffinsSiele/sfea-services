package internal

type Names struct {
	Status NamesStatus `json:"status"`
	Data   []*Name     `json:"data"`
}

type NamesStatus string

const NamesStatusOK NamesStatus = "ok"
