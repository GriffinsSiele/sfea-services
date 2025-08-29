package internal

import (
	"strings"

	"github.com/miekg/dns"
)

type DNSHandler struct{}

func (h *DNSHandler) ServeDNS(w dns.ResponseWriter, r *dns.Msg) {
	msg := &dns.Msg{}
	msg.SetReply(r)
	msg.Authoritative = true

	hostnameToPodLink, hostnameToServiceLink := HostnameToPodLink.Load(), HostnameToServiceLink.Load()

	for _, question := range r.Question {
		name := strings.TrimSuffix(question.Name, ".")

		switch question.Qtype {
		case dns.TypeA:
			if hostnameToPodLink != nil {
				if p, ok := (*hostnameToPodLink)[name]; ok {
					msg.Answer = append(msg.Answer, p.ARec())
					break
				}
			}

			if hostnameToServiceLink != nil {
				if s, ok := (*hostnameToServiceLink)[name]; ok {
					msg.Answer = append(msg.Answer, s.ARec())
				}
			}

		case dns.TypePTR:
			if hostnameToServiceLink != nil {
				if s, ok := (*hostnameToServiceLink)[name]; ok {
					for _, p := range s.Pods {
						msg.Answer = append(msg.Answer, p.PTRLink(&s))
					}
				}
			}
		}
	}

	//goland:noinspection GoUnhandledErrorResult
	w.WriteMsg(msg)
}
