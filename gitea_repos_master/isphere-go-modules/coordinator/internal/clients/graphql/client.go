package graphql

type Client struct {
	*Pool
}

func NewClient(p *Pool) *Client {
	return &Client{p}
}
