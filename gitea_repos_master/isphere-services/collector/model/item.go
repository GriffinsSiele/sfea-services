package model

import "context"

type Item struct {
	context.Context
	ContentType string
	Exchange    string
	RoutingKey  string
	Payload     []byte
	RetryCount  int
	MaxAge      int
}
