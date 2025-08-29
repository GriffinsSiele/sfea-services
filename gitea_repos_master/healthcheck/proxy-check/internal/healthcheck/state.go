package healthcheck

import (
	"net"
	"time"
)

type State struct {
	Proxy            *Proxy
	StartTime        time.Time
	DialDuration     time.Duration
	ConnectDuration  time.Duration
	ResponseDuration time.Duration
	IP               net.IP
	Error            error
}
