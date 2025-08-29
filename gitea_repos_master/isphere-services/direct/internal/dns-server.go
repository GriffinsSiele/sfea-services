package internal

import "github.com/miekg/dns"

func NewDNSServer() *dns.Server {
	return &dns.Server{
		Addr:    ":53",
		Net:     "udp",
		Handler: new(DNSHandler),
	}
}
