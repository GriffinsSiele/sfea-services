package geoip

type Record struct {
	Country *Country `maxminddb:"country"`
}

type Country struct {
	ISOCode string `maxminddb:"iso_code"`
}

func (c *Country) String() string {
	return c.ISOCode
}
