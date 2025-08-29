package internal

type Input struct {
	Phone string `json:"phone"`
}

type Error struct {
	Message     string `json:"error_message"`
	Description string `json:"error_description"`
}

type RosneftResponse struct {
	Code    int             `json:"code"`
	Message string          `json:"message"`
	Errors  []*RosneftError `json:"errors,omitempty"`
}

type RosneftError struct {
	FieldName string `json:"field_name"`
	ErrorText string `json:"error_text"`
}
