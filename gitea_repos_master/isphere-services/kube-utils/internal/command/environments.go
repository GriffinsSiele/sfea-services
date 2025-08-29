package command

import (
	"fmt"
	"os"

	"github.com/fatih/color"
	"github.com/jedib0t/go-pretty/v6/table"
	"github.com/urfave/cli/v2"
	metav1 "k8s.io/apimachinery/pkg/apis/meta/v1"
	"k8s.io/client-go/kubernetes"
)

type Environments struct {
	clientset *kubernetes.Clientset
}

func NewEnvironments(clientset *kubernetes.Clientset) *Environments {
	return &Environments{
		clientset: clientset,
	}
}

func (e *Environments) Describe() *cli.Command {
	return &cli.Command{
		Name:  "environments",
		Usage: "print deployments/statefulsets/etc by environment",
		After: e.Run,
	}
}

func (e *Environments) Run(c *cli.Context) error {
	nodesResp, err := e.clientset.CoreV1().Nodes().List(c.Context, metav1.ListOptions{})
	if err != nil {
		return fmt.Errorf("failed to list nodes: %w", err)
	}

	prodNodes := map[string]bool{}

	for _, node := range nodesResp.Items {
		var prodNode bool
		for _, taint := range node.Spec.Taints {
			if taint.Key == "prod" {
				prodNode = true
				break
			}
		}

		prodNodes[node.Name] = prodNode
	}

	t := table.NewWriter()
	t.SetOutputMirror(os.Stdout)
	t.AppendHeader(table.Row{"Kind", "Ref", "Pod", "On Prod?", "Node"})
	t.SetColumnConfigs([]table.ColumnConfig{
		{Name: "Kind", AutoMerge: true},
		{Name: "Ref", AutoMerge: true},
		{Name: "Node", AutoMerge: true},
	})
	t.SortBy([]table.SortBy{
		{Name: "Kind", Mode: table.Asc},
		{Name: "Ref", Mode: table.Asc},
		{Name: "On Prod?", Mode: table.Asc},
		{Name: "Node", Mode: table.Asc},
		{Name: "Pod", Mode: table.Asc},
	})

	pods, err := e.clientset.CoreV1().Pods("").List(c.Context, metav1.ListOptions{})
	if err != nil {
		return fmt.Errorf("failed to list deployments: %w", err)
	}

	for _, pod := range pods.Items {
		var onProd bool
		if prodNodes[pod.Spec.NodeName] {
			onProd = true
		}

		var onProdStr string
		if onProd {
			onProdStr = color.GreenString("Yes")
		} else {
			onProdStr = color.HiBlackString("No")
		}

		for _, ownerReference := range pod.OwnerReferences {
			t.AppendRow(table.Row{ownerReference.Kind, ownerReference.Name, pod.Name, onProdStr, pod.Spec.NodeName})
		}
	}

	t.Render()

	return nil
}
