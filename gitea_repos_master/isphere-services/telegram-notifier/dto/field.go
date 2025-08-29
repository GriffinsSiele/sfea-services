package dto

type Field struct {
	Title *string `json:"title"`
	Value *string `json:"value"`
	Short *bool   `json:"short"`
}
