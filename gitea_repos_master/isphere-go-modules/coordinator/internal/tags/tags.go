package tags

import (
	"gitea-http.gitea.svc.cluster.local/isphere-go-modules/coordinator/internal/contract"
	"github.com/sirupsen/logrus"
)

type Hook struct{}

func NewHook() *Hook {
	return new(Hook)
}

func (h *Hook) Levels() []logrus.Level {
	return logrus.AllLevels
}

func (h *Hook) Fire(e *logrus.Entry) error {
	if e.Context == nil {
		return nil
	}

	if goid, ok := GetGoroutineID(e.Context); ok {
		e.Data[goroutineIDContextValue.string] = goid
	}

	if scope, ok := GetScope(e.Context); ok {
		e.Data[scopeContextValue.string] = scope
	}

	// fallback context values out of tags context
	if requestID, ok := e.Context.Value(contract.ExtraRequestIDCtxValue).(string); ok {
		e.Data["request_id"] = requestID
	}

	return nil
}
