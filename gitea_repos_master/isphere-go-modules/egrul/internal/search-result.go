package internal

type SearchResult struct {
	ShortName         string `json:"c"`
	ResponsiblePerson string `json:"g"`
	Count             int    `json:"cnt,string"`
	INN               string `json:"i"`
	Type              string `json:"k"`
	FullName          string `json:"n"`
	OGRN              string `json:"o"`
	KPP               string `json:"p"`
	RegistrationDate  Date   `json:"r"`
	Token             string `json:"t"`
	Page              string `json:"pg"`
	Tot               string `json:"tot"`
	District          string `json:"rn"`
	PDFData           any    `json:"pdf"`
}
