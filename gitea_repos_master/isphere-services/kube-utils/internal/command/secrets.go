package command

import (
	"encoding/base64"
	"fmt"
	"os"
	"strings"
	"unicode"

	"github.com/fatih/color"
	"github.com/jedib0t/go-pretty/v6/table"
	"github.com/urfave/cli/v2"
	metav1 "k8s.io/apimachinery/pkg/apis/meta/v1"
	"k8s.io/apimachinery/pkg/runtime/schema"
	"k8s.io/client-go/dynamic"
)

type Secrets struct {
	dynamicClient dynamic.Interface
}

func NewSecrets(dynamicClient dynamic.Interface) *Secrets {
	return &Secrets{
		dynamicClient: dynamicClient,
	}
}

func (s *Secrets) Describe() *cli.Command {
	return &cli.Command{
		Name:   "secrets",
		Usage:  "print ClusterSecret values",
		Action: s.Run,
	}
}

func (s *Secrets) Run(c *cli.Context) error {
	gvr := schema.GroupVersionResource{
		Group:    "clustersecret.io",
		Version:  "v1",
		Resource: "clustersecrets",
	}

	secrets, err := s.dynamicClient.Resource(gvr).List(c.Context, metav1.ListOptions{})
	if err != nil {
		return fmt.Errorf("failed to list secrets: %w", err)
	}

	t := table.NewWriter()
	t.SetOutputMirror(os.Stdout)
	t.AppendHeader(table.Row{"Name", "Key", "Value"})
	t.SetColumnConfigs([]table.ColumnConfig{
		{Name: "Name", AutoMerge: true},
		{Name: "Value", WidthMax: 100},
	})
	t.SortBy([]table.SortBy{{Name: "Key", Mode: table.Asc}})

	grayText := color.New(color.FgHiBlack).SprintfFunc()

	for _, item := range secrets.Items {
		data, ok := item.Object["data"].(map[string]interface{})
		if !ok {
			continue
		}

		for k, v := range data {
			vEncodedStr, ok := v.(string)
			if !ok {
				continue
			}

			vBytes, err := base64.StdEncoding.DecodeString(vEncodedStr)
			if err != nil {
				return fmt.Errorf("failed to decode secret value: %w", err)
			}

			var vStrBuilder strings.Builder

			if len(vBytes) != 0 {
				for _, r := range string(vBytes) {
					if !unicode.IsPrint(r) {
						vStrBuilder.WriteString(grayText("%q", r))
					} else {
						vStrBuilder.WriteRune(r)
					}
				}
			} else {
				vStrBuilder.WriteString(grayText("NULL"))
			}

			t.AppendRow(table.Row{item.GetName(), k, vStrBuilder.String()})
		}

		t.AppendSeparator()
	}

	t.Render()

	return nil
}
