package dto

type Attachment struct {
	Title     *string  `json:"title"`
	TitleLink *string  `json:"title_link"`
	Text      *string  `json:"text"`
	ImageURL  *string  `json:"image_url"`
	Color     *string  `json:"color"`
	Fields    []*Field `json:"fields"`
	MrkdownIn []string `json:"mrkdown_in"`
}

func (t *Attachment) Field(name string) *Field {
	for _, field := range t.Fields {
		if field.Title != nil && *field.Title == name {
			return field
		}
	}

	return nil
}

func (t *Attachment) FieldValue(name string) string {
	field := t.Field(name)
	if field == nil {
		return "-"
	}

	return *field.Value
}
