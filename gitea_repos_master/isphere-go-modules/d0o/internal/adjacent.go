package internal

type Adjacent struct {
	Status AdjacentStatus `json:"status"`
	Data   []*Tel         `json:"data"`
}

type AdjacentStatus string

const AdjacentStatusOK AdjacentStatus = "ok"
