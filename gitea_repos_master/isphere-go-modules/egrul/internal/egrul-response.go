package internal

type EgrulResponse struct {
	Token           string `json:"t"`
	CaptchaRequired bool   `json:"captchaRequired"`
}
