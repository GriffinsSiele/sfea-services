package model

type Request struct {
	ID         int         `mapstructure:"id"`
	Key        string      `mapstructure:"key"`
	Type       RequestType `mapstructure:"type"`
	StartTime  int64       `mapstructure:"starttime"`
	Timeout    int64       `mapstructure:"timeout"`
	RawRequest map[string]any
}

func (t *Request) ReInit(rawRequest map[string]any) {
	if t.Type == "" {
		t.Type = RequestTypeInit
	}

	t.RawRequest = rawRequest

	if _, ok := t.RawRequest["type"]; !ok {
		t.RawRequest["type"] = t.Type
	}
}

type RequestType string

const (
	RequestTypeInit     RequestType = "init"
	RequestTypeCallback RequestType = "callback"
)
