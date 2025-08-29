package internal

type Input struct {
	Email string `json:"email"`
	Phone string `json:"phone"`
}

type Error struct {
	Message     string `json:"error_message"`
	Description string `json:"error_description"`
}

type SberResponse struct {
	Client *struct {
		UUID        string `json:"uuid"`
		CreatedDate string `json:"createdDate"`
		MaskedPhone string `json:"maskedPhone"`
	} `json:"client"`
	Error *struct {
		Code        string `json:"code"`
		Description string `json:"description"`
		Message     string `json:"message"`
	} `json:"error"`
	Status SberStatus `json:"status"`
}

type SberStatus string

const (
	SberStatusSuccess SberStatus = "SUCCESS"
	SberStatusFail    SberStatus = "FAIL"
)
