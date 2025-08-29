package internal

type Ad struct {
	Title       string  `json:"title"`
	Price       float64 `json:"price"`
	Time        *Time   `json:"time"`
	Phone       *Tel    `json:"phone"`
	Name        string  `json:"name"`
	Description string  `json:"description"`
	Location    string  `json:"location"`
	Source      string  `json:"source"`
	Category    string  `json:"category"`
	URL         *URL    `json:"url"`
}
