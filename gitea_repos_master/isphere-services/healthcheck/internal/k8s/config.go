package k8s

import (
	"errors"
	"fmt"
	"os"
	"path"

	"k8s.io/client-go/rest"
	"k8s.io/client-go/tools/clientcmd"
	"k8s.io/client-go/util/homedir"
)

func NewConfig() (*rest.Config, error) {
	filename := path.Join(homedir.HomeDir(), ".kube", "config")

	if _, err := os.Stat(filename); err != nil {
		if !errors.Is(err, os.ErrNotExist) {
			return nil, fmt.Errorf("failed to stat %s: %w", filename, err)
		}

		config, err := rest.InClusterConfig()
		if err != nil {
			return nil, fmt.Errorf("failed to get in-cluster config: %w", err)
		}

		return config, nil
	}

	config, err := clientcmd.BuildConfigFromFlags("", filename)
	if err != nil {
		return nil, fmt.Errorf("failed to build config: %w", err)
	}

	return config, nil
}
