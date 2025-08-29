package dto

type TgResponse struct {
	OK          bool    `json:"ok"`
	Description *string `json:"description"`
}
