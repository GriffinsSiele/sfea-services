package env

import (
	"context"
	"fmt"
	"log/slog"
	"os"

	metav1 "k8s.io/apimachinery/pkg/apis/meta/v1"
	"k8s.io/client-go/kubernetes"
)

type ClusterInfo struct {
	NodeName string
	Hostname string
}

func NewClusterInfo(clientset *kubernetes.Clientset, params *Params) (*ClusterInfo, error) {
	r := new(ClusterInfo)

	// Hostname
	hostname, err := os.Hostname()
	if err != nil {
		return nil, fmt.Errorf("failed to get hostname: %w", err)
	}

	r.Hostname = hostname

	// NodeName
	if namespace := params.Namespace; namespace != "" {
		resp, err := clientset.CoreV1().Pods(namespace).
			Get(context.Background(), hostname, metav1.GetOptions{})
		if err != nil {
			return nil, fmt.Errorf("failed to get self pod: %w", err)
		}

		r.NodeName = resp.Spec.NodeName

		if labelName, ok := resp.Labels["app.kubernetes.io/name"]; ok {
			r.Hostname = labelName
		} else if labelApp, ok := resp.Labels["app"]; ok {
			r.Hostname = labelApp
		} else if labelInstance, ok := resp.Labels["app.kubernetes.io/instance"]; ok {
			r.Hostname = labelInstance
		} else if labelK8sApp, ok := resp.Labels["k8s-app"]; ok {
			r.Hostname = labelK8sApp
		}

		slog.With("namespace", namespace,
			"hostname", r.Hostname,
			"node_name", r.NodeName).InfoContext(context.Background(), "cluster info")
	}

	return r, nil
}
