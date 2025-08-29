package internal

import (
	"context"
	"fmt"
	"net"
	"time"

	v1 "k8s.io/apimachinery/pkg/apis/meta/v1"
	"k8s.io/apimachinery/pkg/types"
	"k8s.io/client-go/kubernetes"
)

type K8sHandler struct {
	clientSet *kubernetes.Clientset
	duration  time.Duration
}

func NewK8sHandler(clientSet *kubernetes.Clientset, duration time.Duration) *K8sHandler {
	return &K8sHandler{
		clientSet: clientSet,
		duration:  duration,
	}
}

func (h *K8sHandler) Handle(ctx context.Context) error {
	if err := h.handle(ctx); err != nil {
		return fmt.Errorf("failed to handle init: %w", err)
	}

	for {
		select {
		case <-ctx.Done():
			return ctx.Err()
		case <-time.After(h.duration):
			if err := h.handle(ctx); err != nil {
				return fmt.Errorf("failed to handle iteration: %w", err)
			}
		}
	}
}

func (h *K8sHandler) handle(ctx context.Context) error {
	pods, err := h.clientSet.CoreV1().Pods("").List(ctx, v1.ListOptions{})
	if err != nil {
		return fmt.Errorf("failed to list pods: %w", err)
	}

	services, err := h.clientSet.CoreV1().Services("").List(ctx, v1.ListOptions{})
	if err != nil {
		return fmt.Errorf("failed to list services: %w", err)
	}

	hostnameToPodLink := make(map[string]PodLink)
	hostnameToServiceLink := make(map[string]ServiceLink)
	nameToPod := make(map[types.UID]*PodLink)

	for _, pod := range pods.Items {
		name := NewPodNameWithPod(pod)
		if name == "" || pod.Status.PodIP == "None" {
			continue
		}

		l := PodLink{
			Namespace: pod.Namespace,
			Name:      name,
			NodeName:  pod.Spec.NodeName,
			IPv4:      net.ParseIP(pod.Status.PodIP),
		}
		hostnameToPodLink[l.Hostname()] = l
		nameToPod[pod.UID] = &l
	}

	for _, service := range services.Items {
		if service.Spec.ClusterIP == "None" {
			continue
		}

		l := ServiceLink{
			Namespace: service.Namespace,
			Name:      service.Name,
			IPv4:      net.ParseIP(service.Spec.ClusterIP),
		}

		if selector := service.Spec.Selector; selector != nil {
			for _, pod := range pods.Items {
				if h.labelsMatch(selector, pod.ObjectMeta.Labels) {
					if podLink, ok := nameToPod[pod.UID]; ok {
						l.Pods = append(l.Pods, podLink)
					}
				}
			}
		}

		hostnameToServiceLink[l.Hostname()] = l
	}

	HostnameToPodLink.Swap(&hostnameToPodLink)
	HostnameToServiceLink.Swap(&hostnameToServiceLink)

	return nil
}

func (h *K8sHandler) labelsMatch(selector map[string]string, labels map[string]string) bool {
	for key, value := range selector {
		if labels[key] != value {
			return false
		}
	}
	return true
}
