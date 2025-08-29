package model

type Location struct {
	Coords []float64 `json:"coords"`
	Text   string    `json:"text"`
}
