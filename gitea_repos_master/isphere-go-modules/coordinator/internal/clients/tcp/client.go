package tcp

type Client struct {
	*Pool
}

func NewClient(pool *Pool) *Client {
	return &Client{pool}
}
