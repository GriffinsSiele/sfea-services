package configuration

import (
	"fmt"
	"os"

	"gopkg.in/yaml.v3"
)

type Upstream struct {
	Routes []*Route
}

func NewUpstreamWithConfigFile(configFile string) (*Upstream, error) {
	upstream := new(Upstream)

	fh, err := os.Open(configFile)
	if err != nil {
		return upstream, fmt.Errorf("failed to open config file: %w", err)
	}
	//goland:noinspection GoUnhandledErrorResult
	defer fh.Close()

	if err := yaml.NewDecoder(fh).Decode(&upstream); err != nil {
		return upstream, fmt.Errorf("failed to decode config file: %w", err)
	}

	return upstream, nil
}
