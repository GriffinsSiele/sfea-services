package contracts

import "context"

type ServerEvents string

const (
	ServerStartedEvent ServerEvents = "ServerStartedEvent"
)

func OnStartServer(ctx context.Context, server Server) {
	if ch, ok := ctx.Value(ServerStartedEvent).(chan Server); ok {
		ch <- server
	}
}
