package internal

type Result interface {
	Type() ResultType
	IsEmpty() bool
}

type ResultType string

const (
	ResultTypeIP      ResultType = "ip"
	ResultTypeService ResultType = "service"
)

// ---

type ResultIP struct {
	IP           string    `json:"ip"`
	Country      string    `json:"country"`
	CountryCode  string    `json:"country_code"`
	Province     string    `json:"province"`
	City         string    `json:"city"`
	Timezone     string    `json:"timezone"`
	Location     *Location `json:"location"`
	ASN          string    `json:"asn"`
	Organization string    `json:"organization"`
}

func (t *ResultIP) Type() ResultType {
	return ResultTypeIP
}

func (t *ResultIP) IsEmpty() bool {
	return t.Country == "" &&
		t.CountryCode == "" &&
		t.Province == "" &&
		t.City == "" &&
		t.Timezone == "" &&
		t.Location == nil &&
		t.ASN == "" &&
		t.Organization == ""
}

// ---

type Location struct {
	Coords []float64 `json:"coords"`
	Text   string    `json:"text"`
}

// ---

type ResultService struct {
	Service   string `json:"service"`
	Port      int    `json:"port"`
	Transport string `json:"transport"`
}

func (t *ResultService) Type() ResultType {
	return ResultTypeService
}

func (t *ResultService) IsEmpty() bool {
	return t.Service == "" &&
		t.Port == 0 &&
		t.Transport == ""
}
