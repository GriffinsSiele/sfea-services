package model

type Field[T any] struct {
	Recorder    `json:"-"`
	Field       string    `json:"field"`
	Description string    `json:"description,omitempty"`
	Title       string    `json:"title,omitempty"`
	Type        FieldType `json:"type"`
	Value       T         `json:"value"`
}

// ---

type FieldType string

const (
	FieldTypeAddress  FieldType = "address"
	FieldTypeDateTime FieldType = "datetime"
	FieldTypeEmail    FieldType = "email"
	FieldTypeFloat    FieldType = "float"
	FieldTypeImage    FieldType = "image"
	FieldTypeInteger  FieldType = "integer"
	FieldTypeMap      FieldType = "map"
	FieldTypeNick     FieldType = "nick"
	FieldTypePhone    FieldType = "phone"
	FieldTypeSkype    FieldType = "skype"
	FieldTypeString   FieldType = "string"
	FieldTypeTelegram FieldType = "telegram"
	FieldTypeText     FieldType = "text"
	FieldTypeURL      FieldType = "url"
)
