package internal

type Ads struct {
	Status AdsStatus `json:"status"`
	Data   []*Ad     `json:"data"`
}

type AdsStatus string

const AdsStatusOK AdsStatus = "ok"
