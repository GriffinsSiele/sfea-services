package internal

import (
	"fmt"
	"net"

	"github.com/miekg/dns"
)

type ServiceLink struct {
	Namespace string
	Name      string
	IPv4      net.IP
	Pods      []*PodLink
}

func (l *ServiceLink) Hostname() string {
	return fmt.Sprintf("%s.%s.svc.cluster.local", l.Name, l.Namespace)
}

func (l *ServiceLink) ARec() *dns.A {
	return &dns.A{
		A: l.IPv4,
		Hdr: dns.RR_Header{
			Name:   l.Hostname() + ".",
			Rrtype: dns.TypeA,
			Class:  dns.ClassINET,
			Ttl:    uint32(K8sHandlerDuration.Seconds()),
		},
	}
}
