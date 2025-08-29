package dto

type Notification struct {
	Alias       *string       `json:"alias"`
	Text        *string       `json:"text"`
	Attachments []*Attachment `json:"attachments"`
	Sections    []*Section    `json:"sections"`
}
