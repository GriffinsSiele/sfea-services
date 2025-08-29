package dto

type CollectorData struct {
	Key  string         `json:"key"`
	Data []*CallbackReq `json:"data"`
}
