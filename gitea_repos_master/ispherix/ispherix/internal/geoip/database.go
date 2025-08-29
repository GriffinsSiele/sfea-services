package geoip

import (
	"context"
	"fmt"
	"net"

	"github.com/oschwald/maxminddb-golang"
)

type Database struct {
	*maxminddb.Reader
}

func NewDatabase() (*Database, error) {
	db, err := maxminddb.Open("var/GeoLite2/GeoLite2-City.mmdb")
	if err != nil {
		return nil, fmt.Errorf("failed to open geoip database: %w", err)
	}

	return &Database{db}, nil
}

func (d *Database) FindCountryByIP(_ context.Context, ip net.IP) (*Country, error) {
	var record Record
	if err := d.Lookup(ip, &record); err != nil {
		return nil, fmt.Errorf("failed to lookup ip: %w", err)
	}

	return record.Country, nil
}

func (d *Database) FindCountryByAddr(_ context.Context, addr net.Addr) (*Country, error) {
	switch v := addr.(type) {
	case *net.TCPAddr:
		return d.FindCountryByIP(context.Background(), v.IP)
	default:
		return nil, fmt.Errorf("unsupported address type: %T", addr)
	}
}
