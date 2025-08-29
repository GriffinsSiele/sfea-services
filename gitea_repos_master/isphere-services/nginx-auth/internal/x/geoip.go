package x

import (
	"fmt"
	"net/http"
	"path"

	"github.com/oschwald/geoip2-golang"
	"i-sphere.ru/nginx-auth/internal/configuration"
)

type GeoIP struct {
	db *geoip2.Reader
}

func NewGeoIP(params *configuration.Params) (*GeoIP, error) {
	c := new(GeoIP)
	if db, err := geoip2.Open(path.Join(params.GeoIPPath, "GeoLite2-Country.mmdb")); err != nil {
		return nil, fmt.Errorf("failed to open GeoLite2-Country.mmdb: %w", err)
	} else {
		c.db = db
	}
	return c, nil
}

func (c *GeoIP) FindCountryCodeByRequest(req *http.Request) (string, error) {
	country, err := c.db.Country(ClientIP(req))
	if err != nil {
		return "", fmt.Errorf("failed to find country: %w", err)
	}
	return country.Country.IsoCode, nil
}
