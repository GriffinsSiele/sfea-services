package strategies

import (
	"context"
	"net"
	"sync"
	"time"

	"go.i-sphere.ru/proxy/pkg/models"
	"go.i-sphere.ru/proxy/pkg/utils"
)

type Fast struct {
}

func NewFast() *Fast {
	return new(Fast)
}

func (f *Fast) Name() string {
	return "fast"
}

func (f *Fast) Reorder(ctx context.Context, proxies []*models.ProxySpec, params ...string) ([]*models.ProxySpec, error) {
	if len(proxies) == 0 {
		return nil, nil
	}

	limit := utils.FirstParamAsIntWithMax(len(proxies), params...)
	if limit == 0 {
		limit = 1
	}

	var wg sync.WaitGroup
	ch := make(chan proxySpecWithDuration)
	elems := make([]*models.ProxySpec, 0, len(proxies))
	done := make(chan any)
	cancelCtx, cancel := context.WithCancel(ctx)
	defer cancel()

	go func() {
		for elem := range ch {
			elems = append(elems, elem.ProxySpec)
			if len(elems) >= limit {
				cancel()
				break
			}
		}
		close(done)
	}()

	for _, proxy := range proxies {
		wg.Add(1)
		go func(proxy *models.ProxySpec) {
			defer wg.Done()
			timeoutCtx, cancel := context.WithTimeout(cancelCtx, 200*time.Millisecond)
			defer cancel()

			start := time.Now()
			conn, err := new(net.Dialer).DialContext(timeoutCtx, "tcp", proxy.Addr())
			if err == nil {
				ch <- proxySpecWithDuration{
					ProxySpec: proxy,
					duration:  time.Since(start),
				}
				//goland:noinspection GoUnhandledErrorResult
				conn.Close()
			}
		}(proxy)
	}

	go func() {
		wg.Wait()
		close(ch)
	}()

	<-done
	return elems, nil
}

type proxySpecWithDuration struct {
	*models.ProxySpec
	duration time.Duration
}
