package managers

import (
	"context"
	"time"

	http "github.com/Danny-Dasilva/fhttp"
	"go.i-sphere.ru/proxy/pkg/models"
)

type ProxySpecLogOptions struct {
	Context                  context.Context
	ProxySpec                *models.ProxySpec
	JA3                      *string
	Request                  *http.Request
	RequestCreatedAt         time.Time
	Response                 *http.Response
	Error                    error
	Master                   bool
	ResponseOrErrorCreatedAt *time.Time
}

func NewProxySpecLogOptions() *ProxySpecLogOptions {
	return &ProxySpecLogOptions{}
}

func (o *ProxySpecLogOptions) WithContext(ctx context.Context) *ProxySpecLogOptions {
	o.Context = ctx
	return o
}

func (o *ProxySpecLogOptions) WithProxySpec(proxySpec *models.ProxySpec) *ProxySpecLogOptions {
	o.ProxySpec = proxySpec
	return o
}

func (o *ProxySpecLogOptions) WithJA3(ja3 *string) *ProxySpecLogOptions {
	o.JA3 = ja3
	return o
}

func (o *ProxySpecLogOptions) WithRequest(req *http.Request) *ProxySpecLogOptions {
	o.Request = req
	o.RequestCreatedAt = time.Now()
	return o
}

func (o *ProxySpecLogOptions) WithResponse(resp *http.Response) *ProxySpecLogOptions {
	o.Response = resp
	now := time.Now()
	o.ResponseOrErrorCreatedAt = &now
	return o
}

func (o *ProxySpecLogOptions) WithError(err error) *ProxySpecLogOptions {
	o.Error = err
	now := time.Now()
	o.ResponseOrErrorCreatedAt = &now
	return o
}

func (o *ProxySpecLogOptions) WithMaster() *ProxySpecLogOptions {
	o.Master = true
	return o
}

func (o *ProxySpecLogOptions) Duration() *time.Duration {
	if o.ResponseOrErrorCreatedAt == nil {
		return nil
	}
	d := o.ResponseOrErrorCreatedAt.Sub(o.RequestCreatedAt)
	return &d
}
