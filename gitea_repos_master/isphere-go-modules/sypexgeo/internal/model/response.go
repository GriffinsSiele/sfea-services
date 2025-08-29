package model

type Response struct {
	IP   string `json:"ip"`
	City struct {
		Location
		Population int    `json:"population"`
		Tel        string `json:"tel"`
		Post       string `json:"post"`
	} `json:"city"`

	Region struct {
		Location
		ISO      string `json:"iso"`
		Timezone string `json:"timezone"`
		Auto     string `json:"auto"`
		UTC      int    `json:"utc"`
	} `json:"region"`

	Country struct {
		Location
		ISO        string `json:"iso"`
		Continent  string `json:"continent"`
		Timezone   string `json:"timezone"`
		Area       int    `json:"area"`
		Population int    `json:"population"`
		CapitalID  int    `json:"capital_id"`
		CapitalRU  string `json:"capital_ru"`
		CapitalEN  string `json:"capital_en"`
		CurCode    string `json:"cur_code"`
		Phone      string `json:"phone"`
		Neighbours string `json:"neighbours"`
		UTC        int    `json:"utc"`
	} `json:"country"`

	Error     string `json:"error"`
	Request   int    `json:"request"`
	Created   string `json:"created"`
	Timestamp int64  `json:"timestamp"`
}

type Location struct {
	ID     int     `json:"id" mapstructure:"id"`
	Lat    float64 `json:"lat" mapstructure:"lat"`
	Lon    float64 `json:"lon" mapstructure:"lon"`
	NameRU string  `json:"name_ru" mapstructure:"name_ru"`
	NameEN string  `json:"name_en" mapstructure:"name_en"`
	NameUK string  `json:"name_uk" mapstructure:"name_uk"`
	NameDE string  `json:"name_de" mapstructure:"name_de"`
	NameFR string  `json:"name_fr" mapstructure:"name_fr"`
	NameIT string  `json:"name_it" mapstructure:"name_it"`
	NameES string  `json:"name_es" mapstructure:"name_es"`
	NamePT string  `json:"name_pt" mapstructure:"name_pt"`
	OKATO  string  `json:"okato" mapstructure:"okato"`
	VK     int     `json:"vk" mapstructure:"vk"`
}
