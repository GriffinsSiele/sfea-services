package k8s

import (
	"fmt"
	"os"
	"path/filepath"

	"k8s.io/client-go/rest"
	"k8s.io/client-go/tools/clientcmd"
	"k8s.io/client-go/util/homedir"
)

func NewConfig() (*rest.Config, error) {
	kubeconfigFile := os.Getenv("KUBECONFIG")
	if kubeconfigFile == "" {
		kubeconfigFile = filepath.Join(homedir.HomeDir(), ".kube", "config")
	}

	if _, err := os.Stat(kubeconfigFile); err == nil {
		if config, err := clientcmd.BuildConfigFromFlags("", kubeconfigFile); err != nil {
			return nil, fmt.Errorf("failed to build config from kubeconfig: %w", err)
		} else {
			return config, nil
		}
	}

	if config, err := rest.InClusterConfig(); err != nil {
		return nil, fmt.Errorf("failed to get in cluster config: %w", err)
	} else {
		return config, nil
	}
}
