package internal

import (
	"sync/atomic"
	"time"
)

var (
	K8sHandlerDuration time.Duration

	HostnameToPodLink     atomic.Pointer[map[string]PodLink]
	HostnameToServiceLink atomic.Pointer[map[string]ServiceLink]
)
