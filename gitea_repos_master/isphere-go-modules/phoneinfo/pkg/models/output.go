package models

type Output struct {
	Source      *string  `json:"source"`
	Type        *string  `json:"type"`
	Provider    *string  `json:"provider"`
	Phone       *string  `json:"phone"`
	CountryCode *string  `json:"country_code"`
	Country     *string  `json:"country"`
	CityCode    *string  `json:"city_code"`
	City        *string  `json:"city"`
	Number      *string  `json:"number"`
	Extension   *string  `json:"extension"`
	Timezone    *string  `json:"timezone"`
	Region      []string `json:"region,omitempty"`
	RegionCode  *int     `json:"region_code,omitempty"`
}
