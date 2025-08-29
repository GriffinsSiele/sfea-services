package internal

import (
	"errors"
	"fmt"
	"net"

	"github.com/graphql-go/graphql"
	"github.com/sirupsen/logrus"
)

type ResultResolver struct{}

func NewResultResolver() *ResultResolver {
	return &ResultResolver{}
}

func (t *ResultResolver) Resolve(p graphql.ResolveParams) (any, error) {
	ip, ok := p.Args["ip"].(string)
	if !ok {
		return nil, errors.New("ip was not provided")
	}

	var (
		results        = make([]*Result, 0)
		hostnames, err = net.LookupAddr(ip)
	)

	if err != nil {
		logrus.WithField("ip", ip).Warnf("lookup addr: %v", err)

		return results, nil
	}

	for _, hostname := range hostnames {
		results = append(results, &Result{Name: hostname})
	}

	return results, nil
}

func (t *ResultResolver) ResolveHosts(p graphql.ResolveParams) (any, error) {
	hostname, ok := p.Source.(*Result)
	if !ok {
		return nil, fmt.Errorf("hostname was not provided")
	}

	var (
		results    = make([]string, 0)
		hosts, err = net.LookupHost(hostname.Name)
	)

	if err != nil {
		logrus.WithField("hostname", hostname.Name).Warnf("lookup host: %v", err)

		return results, nil
	}

	return hosts, nil
}
