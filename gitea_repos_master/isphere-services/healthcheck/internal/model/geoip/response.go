package geoip

import "fmt"

type Response struct {
	Status    string    `json:"status"`
	Code      int       `json:"code"`
	Message   string    `json:"message"`
	Records   []*Record `json:"records"`
	Timestamp int64     `json:"timestamp"`
	Events    []any     `json:"events"`
}

func (r *Response) Validate() error {
	if r.Status != "ok" {
		return fmt.Errorf("unexpected status: %s: with message: %s", r.Status, r.Message)
	}
	if r.Code != 200 {
		return fmt.Errorf("unexpected code: %d: with message: %s", r.Code, r.Message)
	}
	if len(r.Records) == 0 {
		return fmt.Errorf("no records")
	}
	return nil
}

type Record struct {
	IP          string    `json:"ip"`
	CountryCode string    `json:"country_code"`
	Region      string    `json:"region"`
	City        string    `json:"city"`
	Location    *Location `json:"location"`
}

type Location struct {
	Coords [2]float64 `json:"coords"`
	Text   string     `json:"text"`
}
