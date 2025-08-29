package internal

import (
	"fmt"
	"net"

	"github.com/miekg/dns"
	v1 "k8s.io/api/core/v1"
)

type PodLink struct {
	Namespace string
	Name      string
	NodeName  string
	IPv4      net.IP
}

func (l *PodLink) Hostname() string {
	return fmt.Sprintf("%s.%s.pod.%s.local", l.Name, l.Namespace, l.NodeName)
}

func (l *PodLink) ARec() *dns.A {
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

func (l *PodLink) PTRLink(s *ServiceLink) *dns.PTR {
	return &dns.PTR{
		Ptr: l.Hostname() + ".",
		Hdr: dns.RR_Header{
			Name:   s.Hostname() + ".",
			Rrtype: dns.TypePTR,
			Class:  dns.ClassINET,
			Ttl:    uint32(K8sHandlerDuration.Seconds()),
		},
	}
}

func NewPodNameWithPod(pod v1.Pod) string {
	if labelName, ok := pod.Labels["app.kubernetes.io/name"]; ok {
		return labelName
	} else if labelApp, ok := pod.Labels["app"]; ok {
		return labelApp
	} else if labelInstance, ok := pod.Labels["app.kubernetes.io/instance"]; ok {
		return labelInstance
	} else if labelK8sApp, ok := pod.Labels["k8s-app"]; ok {
		return labelK8sApp
	} else {
		return ""
	}
}
