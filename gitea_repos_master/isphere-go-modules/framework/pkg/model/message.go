package model

type Message struct {
	ID        int64  `json:"id" validate:"required"`
	Key       string `json:"key" validate:"required"`
	StartTime int64  `json:"starttime" validate:"required"`
}

func (t *Message) GetKey() string {
	return t.Key
}
