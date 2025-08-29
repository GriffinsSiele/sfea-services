package tcp

import (
	"context"

	"gitea-http.gitea.svc.cluster.local/isphere-go-modules/coordinator/internal/config"
)

type connContextType struct{ string }

type retryContextType struct{ string }

var connContextProxy = connContextType{"proxy"}

var retryContextProxy = retryContextType{"proxy"}

func ContextWithProxy(ctx context.Context, proxy *config.Proxy) context.Context {
	return context.WithValue(ctx, connContextProxy, proxy)
}

func ContextWithRetry(ctx context.Context, retry *config.UpstreamTCPRetry) context.Context {
	return context.WithValue(ctx, retryContextProxy, retry)
}

func ContextGetProxy(ctx context.Context) (*config.Proxy, bool) {
	if proxy, ok := ctx.Value(connContextProxy).(*config.Proxy); ok {
		return proxy, true
	}
	return nil, false
}

func ContextGetRetry(ctx context.Context) (*config.UpstreamTCPRetry, bool) {
	if retry, ok := ctx.Value(retryContextProxy).(*config.UpstreamTCPRetry); ok {
		return retry, true
	}
	return nil, false
}
