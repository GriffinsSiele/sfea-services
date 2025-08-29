package configuration

import (
	"flag"
	"fmt"
	"path"
	"path/filepath"
)

type Params struct {
	ConfigFile string
	HTTP       *Server
	HTTPS      *HTTPS
	Upstreams  []*Upstream
	ServerName string
	GeoIPPath  string
	Local      bool
}

func NewParams() (*Params, error) {
	params := &Params{
		HTTP:  &Server{},
		HTTPS: &HTTPS{},
	}

	flag.StringVar(&params.ConfigFile, "config-file", path.Join("config", "/*.yaml"), "Config file")
	flag.StringVar(&params.HTTPS.Host, "https.host", "127.0.0.1", "HTTPS host")
	flag.Uint64Var(&params.HTTPS.Port, "https.port", 443, "HTTPS port")
	flag.StringVar(&params.HTTPS.CertFile, "https.cert-file", "ssl/server.crt", "HTTPS cert file")
	flag.StringVar(&params.HTTPS.KeyFile, "https.key-file", "ssl/server.key", "HTTPS key file")
	flag.StringVar(&params.HTTPS.LetsEncryptCacheDir, "https.letsencrypt-cache-dir", "ssl/letsencrypt", "HTTPS letsencrypt cache dir")
	flag.StringVar(&params.HTTP.Host, "http.host", "127.0.0.1", "HTTP host")
	flag.Uint64Var(&params.HTTP.Port, "http.port", 80, "HTTP port")
	flag.StringVar(&params.ServerName, "server-name", "SpheriX/1.23.4", "Server name")
	flag.StringVar(&params.GeoIPPath, "geoip-path", path.Join("share", "GeoIP"), "GeoIP root folder")
	flag.BoolVar(&params.Local, "local", false, "Local mode")
	flag.Parse()

	configFiles, err := filepath.Glob(params.ConfigFile)
	if err != nil {
		return nil, fmt.Errorf("failed to glob config files: %w", err)
	}

	params.Upstreams = make([]*Upstream, len(configFiles))
	for i, configFile := range configFiles {
		if upstream, err := NewUpstreamWithConfigFile(configFile); err != nil {
			return nil, fmt.Errorf("failed to create upstreams: %s: %w", configFile, err)
		} else {
			params.Upstreams[i] = upstream
		}
	}

	return params, nil
}
