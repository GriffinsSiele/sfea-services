package healthcheck

import (
	"context"
	"fmt"
	"io"
	"net"
	"net/http"
	"net/url"
	"strconv"
	"sync"
	"time"
)

type Healthcheck struct {
	endpoint string
}

func NewHealthcheck(endpoint string) *Healthcheck {
	return &Healthcheck{
		endpoint: endpoint,
	}
}

func (h *Healthcheck) CheckAll(ctx context.Context, proxies []*Proxy) (States, error) {
	states := make([]*State, len(proxies))

	var wg sync.WaitGroup
	errCh := make(chan error)

	for i := range proxies {
		wg.Add(1)

		go func(i int) {
			defer wg.Done()

			state, err := h.Check(ctx, proxies[i])
			if err != nil {
				errCh <- err
				return
			}

			states[i] = state
		}(i)
	}

	go func() {
		wg.Wait()
		close(errCh)
	}()

	if err := <-errCh; err != nil {
		return nil, err
	}

	return states, nil
}

func (h *Healthcheck) Check(ctx context.Context, proxy *Proxy) (*State, error) {
	req, err := http.NewRequestWithContext(ctx, http.MethodGet, h.endpoint, http.NoBody)
	if err != nil {
		return nil, fmt.Errorf("failed to create check request: %w", err)
	}

	state := &State{
		Proxy:     proxy,
		StartTime: time.Now(),
	}

	//goland:noinspection HttpUrlsUsage
	proxyURL := &url.URL{
		Scheme: "http",
		Host:   net.JoinHostPort(proxy.Host, strconv.Itoa(proxy.Port)),
	}

	if proxy.Username != "" && proxy.Password != "" {
		proxyURL.User = url.UserPassword(proxy.Username, proxy.Password)
	}
	if err != nil {
		state.Error = fmt.Errorf("failed to parse proxy url: %w", err)
		return state, nil
	}

	transport := &http.Transport{
		Proxy: http.ProxyURL(proxyURL),
		DialContext: func(ctx context.Context, network, addr string) (net.Conn, error) {
			conn, err := new(net.Dialer).DialContext(ctx, network, addr)
			if err != nil {
				return nil, err
			}
			state.DialDuration = time.Since(state.StartTime)
			return conn, err
		},
		OnProxyConnectResponse: func(context.Context, *url.URL, *http.Request, *http.Response) error {
			state.ConnectDuration = time.Since(state.StartTime)
			return nil
		},
	}

	client := &http.Client{
		Transport: transport,
	}

	resp, err := client.Do(req)
	if err != nil {
		state.Error = fmt.Errorf("failed to do request: %w", err)
		return state, nil
	}
	//goland:noinspection GoUnhandledErrorResult
	defer resp.Body.Close()

	state.ResponseDuration = time.Since(state.StartTime)

	respBody, err := io.ReadAll(resp.Body)
	if err != nil {
		state.Error = fmt.Errorf("failed to read response: %w", err)
		return state, nil
	}

	if state.IP = net.ParseIP(string(respBody)); state.IP == nil {
		state.Error = fmt.Errorf("unexpected response, invalid ip address: %s", string(respBody))
		return state, nil
	}

	return state, nil
}
