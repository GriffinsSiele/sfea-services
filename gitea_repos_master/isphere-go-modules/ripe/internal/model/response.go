package model

type Response struct {
	Service struct {
		Name string `json:"name"`
	} `json:"service"`

	Parameters struct {
		InverseLookup any              `json:"inverse-lookup"`
		TypeFilters   any              `json:"type-filters"`
		Flags         any              `json:"flags"`
		QueryStrings  map[string][]any `json:"query-strings"`
		Sources       any              `json:"sources"`
	} `json:"parameters"`

	Objects struct {
		Object []struct {
			Type string `json:"type"`

			Link Link `json:"link"`

			Source struct {
				ID string `json:"ID"`
			} `json:"source"`

			PrimaryKey struct {
				Attribute []struct {
					Name  string `json:"name"`
					Value string `json:"value"`
				} `json:"attribute"`
			} `json:"primary-key"`

			Attributes struct {
				Attribute []struct {
					Name           string `json:"name"`
					Value          string `json:"value"`
					Link           Link   `json:"link"`
					ReferencedType string `json:"referenced-type"`
				} `json:"attribute"`
			} `json:"attributes"`
		} `json:"object"`
	} `json:"objects"`

	TermsAndConditions Link `json:"terms-and-conditions"`

	Version struct {
		Version   string `json:"version"`
		Timestamp string `json:"timestamp"`
		CommitID  string `json:"commit-id"`
	} `json:"version"`
}
