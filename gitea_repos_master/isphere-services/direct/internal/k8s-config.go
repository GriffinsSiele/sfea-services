package internal

import (
	"context"
	"fmt"
	"log/slog"
	"os"
	"path"

	"k8s.io/client-go/rest"
	"k8s.io/client-go/tools/clientcmd"
	"k8s.io/client-go/util/homedir"
)

func NewK8sConfig(ctx context.Context) (*rest.Config, error) {
	var config *rest.Config
	filename := path.Join(homedir.HomeDir(), ".kube", "config")

	if _, err := os.Stat(filename); err == nil {
		slog.With("filename", filename).InfoContext(ctx, "using kubeconfig")
		if config, err = clientcmd.BuildConfigFromFlags("", filename); err != nil {
			return nil, fmt.Errorf("failed to create config from flags: %w", err)
		}
	} else {
		slog.InfoContext(ctx, "using in-cluster config")
		if config, err = rest.InClusterConfig(); err != nil {
			return nil, fmt.Errorf("failed to create in-cluster config: %w", err)
		}
	}

	return config, nil
}
