package model

import "encoding/json"

type Supplements struct {
	Supplement []*Supplement `xml:"supplement"`
}

func (t *Supplements) MarshalJSON() ([]byte, error) {
	return json.Marshal(t.Supplement)
}
