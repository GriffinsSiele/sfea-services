package main_test

import (
	"context"
	"encoding/json"
	"flag"
	"net/url"
	"testing"

	http "github.com/Danny-Dasilva/fhttp"
	tls "github.com/refraction-networking/utls"
	"github.com/stretchr/testify/assert"
	urfavecli "github.com/urfave/cli/v2"
	"go.i-sphere.ru/proxy/pkg/cli"
	"go.i-sphere.ru/proxy/pkg/commands/servers"
	"go.i-sphere.ru/proxy/pkg/contracts"
	"go.uber.org/fx"
)

const (
	selfSOCKS5ProxyURL string = "socks5://0.0.0.0:1080"
	remoteTestURL      string = "https://infosfera.ru/.well-known/connection"
	safariJA3Str       string = "771,4865-4866-4867-49196-49195-52393-49200-49199-52392-49162-49161-49172-49171-157-156-53-47-49160-49170-10,0-5-10-11-13-16-18-23-27-43-45-51-65281,29-23-24-25,0"
	safariUserAgent    string = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.3.1 Safari/605.1.15"
)

func TestSimple(t *testing.T) {
	runServerTest(t, func(ctx context.Context) {
		req, err := http.NewRequestWithContext(ctx, http.MethodGet, remoteTestURL, http.NoBody)
		assert.NoError(t, err)

		resp, err := newClient(t).Do(req)
		assert.NoError(t, err)

		assert.Equal(t, http.StatusOK, resp.StatusCode)

		assert.True(t, resp.Header.Get(string(contracts.XRequestID)) != "")
	})
}

func TestThroughProxyWithCountry(t *testing.T) {
	runServerTest(t, func(ctx context.Context) {
		req, err := http.NewRequestWithContext(ctx, http.MethodGet, remoteTestURL, http.NoBody)
		assert.NoError(t, err)

		req.Header.Set(string(contracts.XSphereProxySpecCountryCode), "de")

		resp, err := newClient(t).Do(req)
		assert.NoError(t, err)
		//goland:noinspection GoUnhandledErrorResult
		defer resp.Body.Close()

		assert.Equal(t, http.StatusOK, resp.StatusCode)

		assert.True(t, resp.Header.Get(string(contracts.XRequestID)) != "")
		assert.True(t, resp.Header.Get(string(contracts.XSphereProxySpecID)) != "")
		assert.Equal(t, "de", resp.Header.Get(string(contracts.XSphereProxySpecCountryCode)))

		var response struct {
			CountryCode string `json:"country_code"`
		}

		assert.NoError(t, json.NewDecoder(resp.Body).Decode(&response))
		assert.Equal(t, "DE", response.CountryCode)
	})
}

func TestThroughProxyWithJA3(t *testing.T) {
	runServerTest(t, func(ctx context.Context) {
		req, err := http.NewRequestWithContext(ctx, http.MethodGet, remoteTestURL, http.NoBody)
		assert.NoError(t, err)

		req.Header.Set("User-Agent", safariUserAgent)
		req.Header.Set(string(contracts.XSphereJA3), safariJA3Str)

		resp, err := newClient(t).Do(req)
		assert.NoError(t, err)
		//goland:noinspection GoUnhandledErrorResult
		defer resp.Body.Close()

		assert.Equal(t, http.StatusOK, resp.StatusCode)

		assert.True(t, resp.Header.Get(string(contracts.XRequestID)) != "")
		assert.True(t, resp.Header.Get(string(contracts.XSphereJA3)) != "")

		var response struct {
			TLS struct {
				JA3 string `json:"ja3"`
			} `json:"tls"`
		}

		assert.NoError(t, json.NewDecoder(resp.Body).Decode(&response))
		assert.Equal(t, safariJA3Str, response.TLS.JA3)
	})
}

func newClient(t *testing.T) *http.Client {
	selfURL, err := url.Parse(selfSOCKS5ProxyURL)
	assert.NoError(t, err)

	return &http.Client{
		Transport: &http.Transport{
			Proxy: http.ProxyURL(selfURL),
			TLSClientConfig: &tls.Config{
				InsecureSkipVerify: true,
			},
		},
	}
}

func runServerTest(t *testing.T, ready func(context.Context)) {
	cli.MustLoadEnv()

	fx.New(cli.Provide(),
		fx.Invoke(func(
			application *cli.App,
			socks5Server *servers.SOCKS5,
			tlsServer *servers.TLS,
			shutdowner fx.Shutdowner,
		) {
			defer func() {
				assert.NoError(t, shutdowner.Shutdown())
			}()

			serverStartedChan := make(chan contracts.Server, 2)
			ctxWithServerStartedChan := context.WithValue(context.Background(), contracts.ServerStartedEvent, serverStartedChan)
			cancelCtx, cancel := context.WithCancel(ctxWithServerStartedChan)
			defer cancel()

			flagSet := flag.NewFlagSet(application.Name, 0)
			parentCtx := &urfavecli.Context{
				Context: cancelCtx,
			}

			go func() {
				assert.NoError(t, socks5Server.Start(urfavecli.NewContext(application.App, flagSet, parentCtx)))
			}()

			go func() {
				assert.NoError(t, tlsServer.Start(urfavecli.NewContext(application.App, flagSet, parentCtx)))
			}()

			for i := 0; i < 2; i++ {
				<-serverStartedChan
			}
			close(serverStartedChan)

			ready(cancelCtx)
		})).
		Run()
}
