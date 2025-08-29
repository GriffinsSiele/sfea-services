package internal

import (
	"context"
	"fmt"
	"net"
	"strings"
	"time"
)

type Shodan struct {
	client *Client
}

func NewShodan(client *Client) *Shodan {
	return &Shodan{
		client: client,
	}
}

func (t *Shodan) Find(ctx context.Context, ip net.IP) (*ShodanResponse, error) {
	var resp ShodanResponse
	if err := t.client.GET(ctx, fmt.Sprintf("/shodan/host/%s", ip), &resp); err != nil {
		return nil, fmt.Errorf("failed to execute ShodanResponse: %w", err)
	}

	return &resp, nil
}

func (t *Shodan) Normalize(response *ShodanResponse) ([]Result, error) {
	var result []Result

	ip := &ResultIP{
		IP:           response.IPStr,
		CountryCode:  response.CountryCode,
		Country:      response.CountryName,
		City:         response.City,
		Organization: response.Org,
		Provider:     response.ISP,
		ASN:          response.ASN,
		Hostnames:    response.Hostnames,
		OS:           response.OS,
		Ports:        response.Ports,
		Tags:         response.Tags,
	}

	if response.Latitude != 0 && response.Longitude != 0 {
		ip.Location = &Location{
			Coords: [2]float64{
				response.Latitude,
				response.Longitude,
			},
		}
	}

	if !ip.IsEmpty() {
		result = append(result, ip)
	}

	for _, item := range response.Data {
		service := &ResultService{
			Service:   item.Shodan.Module,
			Port:      item.Port,
			Transport: item.Transport,
			Product:   item.Product,
			Version:   item.Version,
			Tags:      item.Tags,
		}

		if !service.IsEmpty() {
			result = append(result, service)
		}
	}

	return result, nil
}

// ---

type ShodanResponse struct {
	AreaCode    string        `json:"area_code"`
	ASN         string        `json:"asn"`
	City        string        `json:"city"`
	CountryCode string        `json:"country_code"`
	CountryName string        `json:"country_name"`
	Data        []*ShodanData `json:"data"`
	Domains     []string      `json:"domains"`
	Hostnames   []string      `json:"hostnames"`
	IP          int64         `json:"ip"`
	IPStr       string        `json:"ip_str"`
	ISP         string        `json:"isp"`
	LastUpdate  *DateTime     `json:"last_update"`
	Latitude    float64       `json:"latitude"`
	Longitude   float64       `json:"longitude"`
	Org         string        `json:"org"`
	OS          string        `json:"os"`
	Ports       []int         `json:"ports"`
	RegionCode  string        `json:"region_code"`
	Tags        []string      `json:"tags"`
	VULNS       []string      `json:"vulns"`
}

type ShodanData struct {
	ASN       string                  `json:"asn"`
	CPE       []string                `json:"cpe"`
	CPE23     []string                `json:"cpe23"`
	Data      string                  `json:"data"`
	Domains   []string                `json:"domains"`
	Hash      int64                   `json:"hash"`
	Hostnames []string                `json:"hostnames"`
	HTTP      *ShodanHTTP             `json:"http"`
	Info      string                  `json:"info"`
	IP        int64                   `json:"ip"`
	IPStr     string                  `json:"ip_str"`
	ISP       string                  `json:"isp"`
	Location  *ShodanLocation         `json:"location"`
	Opts      any                     `json:"opts"`
	Org       string                  `json:"org"`
	OS        string                  `json:"os"`
	Port      int                     `json:"port"`
	Product   string                  `json:"product"`
	Shodan    *ShodanMeta             `json:"_shodan"`
	SSH       *ShodanSSH              `json:"ssh"`
	SSL       *ShodanSSL              `json:"ssl"`
	Tags      []string                `json:"tags"`
	Timestamp *DateTime               `json:"timestamp"`
	Transport string                  `json:"transport"`
	Version   string                  `json:"version"`
	VULNS     map[string]*ShodanVULNS `json:"vulns"`
}

type ShodanLocation struct {
	AreaCode    string  `json:"area_code"`
	City        string  `json:"city"`
	CountryCode string  `json:"country_code"`
	CountryName string  `json:"country_name"`
	Latitude    float64 `json:"latitude"`
	Longitude   float64 `json:"longitude"`
	RegionCode  string  `json:"region_code"`
}

type ShodanSSH struct {
	Cipher      string     `json:"cipher"`
	Fingerprint string     `json:"fingerprint"`
	HashSH      string     `json:"hashsh"`
	Kex         *ShodanKex `json:"kex"`
	Key         string     `json:"key"`
	MAC         string     `json:"mac"`
	Type        string     `json:"type"`
}

type ShodanKex struct {
	CompressionAlgorithms   []string `json:"compression_algorithms"`
	EncryptionAlgorithms    []string `json:"encryption_algorithms"`
	KexAlgorithms           []string `json:"kex_algorithms"`
	KeyFollows              bool     `json:"key_follows"`
	Languages               []string `json:"languages"`
	ServerHostKeyAlgorithms []string `json:"server_host_key_algorithms"`
	Unused                  int      `json:"unused"`
}

type ShodanMeta struct {
	Crawler string `json:"crawler"`
	ID      string `json:"id"`
	Module  string `json:"module"`
	Options any    `json:"options"`
	Ptr     bool   `json:"ptr"`
	Region  string `json:"region"`
}

type ShodanHTTP struct {
	Components      any    `json:"components"`
	HeadersHash     int64  `json:"headers_hash"`
	Host            string `json:"host"`
	HTML            string `json:"html"`
	HTMLHash        int64  `json:"html_hash"`
	Location        string `json:"location"`
	Redirects       []any  `json:"redirects"`
	Robots          any    `json:"robots"`
	RobotsHash      int64  `json:"robots_hash"`
	SecurityTXT     any    `json:"securitytxt"`
	SecurityTXTHash int64  `json:"securitytxt_hash"`
	Server          string `json:"server"`
	Sitemap         any    `json:"sitemap"`
	SitemapHash     int64  `json:"sitemap_hash"`
	Status          int    `json:"status"`
	Title           string `json:"title"`
}

type ShodanVULNS struct {
	CVSS       float32  `json:"cvss"`
	References []string `json:"references"`
	Summary    string   `json:"summary"`
	Verified   bool     `json:"verified"`
}

type ShodanSSL struct {
	AcceptableCAS   []any           `json:"acceptable_cas"`
	ALPN            []any           `json:"alpn"`
	Cert            *ShodanCert     `json:"cert"`
	Chain           []string        `json:"chain"`
	ChainSHA256     []string        `json:"chain_sha256"`
	Cipher          *ShodanCipher   `json:"cipher"`
	DHParams        *ShodanDHParams `json:"dhparams"`
	HandshakeStates []string        `json:"handshakeStates"`
	JA3S            string          `json:"ja3s"`
	JARM            string          `json:"jarm"`
	OCSP            any             `json:"ocsp"`
	TLSExt          []*ShodanTLSExt `json:"tlsext"`
	Trust           *ShodanTrust    `json:"trust"`
	Versions        []string        `json:"versions"`
}

type ShodanDHParams struct {
	Bits      int    `json:"bits"`
	Generator int    `json:"generator"`
	Prime     string `json:"prime"`
	PublicKey string `json:"publicKey"`
}

type ShodanTLSExt struct {
	ID   int    `json:"id"`
	Name string `json:"name"`
}

type ShodanCert struct {
	Expired     bool                    `json:"expired"`
	Expires     *DateTimeWhitespaceLess `json:"expires"`
	Extensions  []*ShodanExtension      `json:"extensions"`
	Fingerprint map[string]string       `json:"fingerprint"`
	Issued      *DateTimeWhitespaceLess `json:"issued"`
	Issuer      map[string]string       `json:"issuer"`
	PubKey      *ShodanPubKey           `json:"pubkey"`
	Serial      float64                 `json:"serial"`
	SigAlg      string                  `json:"sig_alg"`
	Subject     map[string]string       `json:"subject"`
	Version     int                     `json:"version"`
}

type ShodanExtension struct {
	Critical bool   `json:"critical"`
	Data     string `json:"data"`
	Name     string `json:"name"`
}

type ShodanPubKey struct {
	Bits int    `json:"bits"`
	Type string `json:"type"`
}

type ShodanCipher struct {
	Bits    int    `json:"bits"`
	Name    string `json:"name"`
	Version string `json:"version"`
}

type ShodanTrust struct {
	Browser any  `json:"browser"`
	Revoked bool `json:"revoked"`
}

// ---

type DateTime struct {
	*time.Time
}

func (t *DateTime) UnmarshalJSON(marshaled []byte) error {
	marshaledString := strings.Trim(string(marshaled), `"`)
	if marshaledString == "null" {
		return nil
	}

	parsed, err := time.Parse("2006-01-02T15:04:05.000000", marshaledString)
	if err != nil {
		return fmt.Errorf("failed to parse time: %w", err)
	}

	t.Time = &parsed

	return nil
}

// ---

type DateTimeWhitespaceLess struct {
	*time.Time
}

func (t *DateTimeWhitespaceLess) UnmarshalJSON(marshaled []byte) error {
	marshaledString := strings.Trim(string(marshaled), `"`)
	if marshaledString == "null" {
		return nil
	}

	parsed, err := time.Parse("20060102150405Z", marshaledString)
	if err != nil {
		return fmt.Errorf("failed to parse time: %w", err)
	}

	t.Time = &parsed

	return nil
}
