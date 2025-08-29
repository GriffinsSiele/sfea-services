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
	CountryCode  string    `json:"country_code"`
	Country      string    `json:"country"`
	City         string    `json:"city"`
	Location     *Location `json:"location"`
	Organization string    `json:"organization"`
	Provider     string    `json:"provider"`
	ASN          string    `json:"asn"`
	Hostnames    []string  `json:"hostnames"`
	OS           string    `json:"os"`
	Ports        []int     `json:"ports"`
	Tags         []string  `json:"tags"`
}

func (t *ResultIP) Type() ResultType {
	return ResultTypeIP
}

func (t *ResultIP) IsEmpty() bool {
	return t.Country == "" &&
		t.CountryCode == "" &&
		t.City == "" &&
		t.Location == nil &&
		t.Organization == "" &&
		t.Provider == "" &&
		t.ASN == "" &&
		len(t.Hostnames) == 0 &&
		t.OS == "" &&
		len(t.Ports) == 0 &&
		len(t.Tags) == 0
}

// ---

type Location struct {
	Coords [2]float64 `json:"coords"`
	Text   string     `json:"text"`
}

// ---

type ResultService struct {
	Service   string   `json:"service"`
	Port      int      `json:"port"`
	Transport string   `json:"transport"`
	Product   string   `json:"product"`
	Version   string   `json:"version"`
	Tags      []string `json:"tags"`
}

func (t *ResultService) Type() ResultType {
	return ResultTypeService
}

func (t *ResultService) IsEmpty() bool {
	return t.Service == "" &&
		t.Port == 0 &&
		t.Transport == "" &&
		t.Product == "" &&
		t.Version == "" &&
		len(t.Tags) == 0
}
