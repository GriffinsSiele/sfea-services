package internal

import "git.i-sphere.ru/isphere-go-modules/framework/pkg/contract"

type MessageFactory struct{}

func NewMessageFactory() *MessageFactory {
	return &MessageFactory{}
}

func (t *MessageFactory) New() contract.Message {
	return &Message{}
}
