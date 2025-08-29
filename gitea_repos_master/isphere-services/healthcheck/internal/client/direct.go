package client

import (
	"context"
	"fmt"
	"net"
	"net/http"
	"strings"
	"time"

	"github.com/miekg/dns"
	"i-sphere.ru/healthcheck/internal/env"
)

type Direct struct {
	params *env.Params
}

func NewDirect(params *env.Params) *Direct {
	return &Direct{
		params: params,
	}
}

func (d *Direct) LookupPTR(addr string) ([]*PTR, error) {
	m := new(dns.Msg)
	m.SetQuestion(dns.Fqdn(addr), dns.TypePTR)
	m.SetEdns0(2<<12, true)

	c := new(dns.Client)
	resp, _, err := c.Exchange(m, net.JoinHostPort(d.params.DirectHost, "53"))
	if err != nil {
		return nil, fmt.Errorf("failed to exchange: %w", err)
	}

	pointers := make([]*PTR, 0, len(resp.Answer))
	for _, answer := range resp.Answer {
		if rec, ok := answer.(*dns.PTR); ok {
			pointers = append(pointers, NewPTRWithString(rec.Ptr))
		}
	}

	return pointers, nil
}

func (d *Direct) NewDialer() *net.Dialer {
	resolver := net.Resolver{
		PreferGo: true,
		Dial: func(ctx context.Context, network, address string) (net.Conn, error) {
			dialer := &net.Dialer{}
			dial, err := dialer.DialContext(ctx, network, net.JoinHostPort(d.params.DirectHost, "53"))
			if err != nil {
				return nil, fmt.Errorf("failed to dial: %w", err)
			}

			return dial, nil
		},
	}

	dialer := &net.Dialer{
		Resolver: &resolver,
	}

	return dialer
}

type PTR struct {
	Name      string
	Namespace string
	NodeName  string
	s         string
}

func NewPTRWithString(s string) *PTR {
	parts := strings.Split(s, ".")
	return &PTR{
		Name:      parts[0],
		Namespace: parts[1],
		NodeName:  parts[3],
		s:         strings.TrimSuffix(s, "."),
	}
}

func (p *PTR) String() string {
	return p.s
}

type DirectTripper struct {
	Transport http.RoundTripper
	Resolver  *net.Resolver
}

func (t *DirectTripper) RoundTrip(r *http.Request) (*http.Response, error) {
	ctx, cancel := context.WithTimeout(r.Context(), 10*time.Second)
	defer cancel()

	ip, err := t.Resolver.LookupIP(ctx, "ip", r.URL.Hostname())
	if err != nil {
		return nil, fmt.Errorf("failed to lookup ip: %w", err)
	}

	r.URL.Host = net.JoinHostPort(ip[0].String(), r.URL.Port())

	return t.Transport.RoundTrip(r)
}

func NewClientWithParams(p *env.Params) *http.Client {
	resolver := &net.Resolver{
		PreferGo: true,
		Dial: func(ctx context.Context, network, addr string) (net.Conn, error) {
			return (&net.Dialer{}).DialContext(ctx, network, p.DirectHost+":53")
		},
	}

	tripper := &DirectTripper{
		Transport: http.DefaultTransport,
		Resolver:  resolver,
	}

	return &http.Client{
		Transport: tripper,
	}
}
