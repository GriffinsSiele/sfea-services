package util

import (
	"encoding/xml"
	"errors"
	"fmt"
	"html/template"
	"os"
	"reflect"

	"github.com/davecgh/go-spew/spew"
	client "github.com/ory/kratos-client-go"
)

type UI struct {
}

func NewUI() *UI {
	return &UI{}
}

func (t *UI) GetNodeLabel(node client.UiNode) (label string) {
	attributes := node.GetAttributes()

	if attributes.UiNodeAnchorAttributes != nil {
		label = attributes.UiNodeAnchorAttributes.Title.GetText()
	} else if attributes.UiNodeInputAttributes != nil {
		label = attributes.UiNodeInputAttributes.Label.GetText()
	}

	if label == "" {
		label = node.Meta.Label.GetText()
	}

	return
}

func (t *UI) IsUINodeAnchorAttributes(node client.UiNode) bool {
	return node.Attributes.UiNodeAnchorAttributes != nil
}

func (t *UI) IsUINodeImageAttributes(node client.UiNode) bool {
	return node.Attributes.UiNodeImageAttributes != nil
}

func (t *UI) IsUINodeInputAttributes(node client.UiNode) bool {
	return node.Attributes.UiNodeInputAttributes != nil
}

func (t *UI) IsUINodeScriptAttributes(node client.UiNode) bool {
	return node.Attributes.UiNodeScriptAttributes != nil
}

func (t *UI) IsUINodeTextAttributes(node client.UiNode) bool {
	return node.Attributes.UiNodeTextAttributes != nil
}

func (t *UI) GetNodeID(node client.UiNode) string {
	attributes := node.GetAttributes()

	switch {
	// GetId
	case attributes.UiNodeAnchorAttributes != nil:
		return attributes.UiNodeAnchorAttributes.GetId()
	case attributes.UiNodeImageAttributes != nil:
		return attributes.UiNodeImageAttributes.GetId()
	case attributes.UiNodeScriptAttributes != nil:
		return attributes.UiNodeScriptAttributes.GetId()
	case attributes.UiNodeTextAttributes != nil:
		return attributes.UiNodeTextAttributes.GetId()

	// GetName
	case attributes.UiNodeInputAttributes != nil:
		return attributes.UiNodeInputAttributes.GetName()

	default:
		return ""
	}
}

func (t *UI) GetNodeInputType(node client.UiNode) string {
	attributes := node.GetAttributes()

	if attributes.UiNodeInputAttributes == nil {
		return ""
	}

	return attributes.UiNodeInputAttributes.GetType()
}

func (t *UI) FilterNodesByGroups(nodes []client.UiNode, groups ...string) (result []client.UiNode) {
	filters := make([]string, 0)

	for _, group := range groups {
		if group == "" {
			continue
		}

		filters = append(filters, group)
	}

	if len(filters) == 0 {
		return nodes
	}

	for _, node := range nodes {
		var expect bool

		for _, filter := range filters {
			if node.GetGroup() == filter || node.GetGroup() == "default" {
				expect = true

				break
			}
		}

		if !expect {
			continue
		}

		result = append(result, node)
	}

	return
}

func (t *UI) Dump(v ...any) template.HTML {
	return template.HTML(spew.Sprintf("<xmp>%s</xmp>", spew.Sdump(v...)))
}

func (t *UI) IsDebug() bool {
	return os.Getenv("APP_ENV") == "dev"
}

func (t *UI) Dict(values ...any) (map[string]any, error) {
	if len(values) == 0 {
		return nil, errors.New("empty dictionary")
	}

	dict := make(map[string]any, 0)

	for index := 0; index < len(values); index++ {
		key, ok := values[index].(string)

		if !ok {
			if reflect.TypeOf(values[index]).Kind() == reflect.Map {
				m := values[index].(map[string]any)

				for j, v := range m {
					dict[j] = v
				}
			} else {
				return nil, errors.New("dictionary values must be map")
			}
		} else {
			index++

			if index == len(values) {
				return nil, fmt.Errorf("specify the key for non array values")
			}

			dict[key] = values[index]
		}
	}

	return dict, nil
}

type DangerouslyImage struct {
	XMLName xml.Name `xml:"img"`
	Class   string   `xml:"class,attr,omitempty"`
	Src     string   `xml:"src,attr,omitempty"`
	Width   int64    `xml:"width,attr,omitempty"`
	Height  int64    `xml:"height,attr,omitempty"`
	Alt     string   `xml:"alt,attr,omitempty"`
}

func (t *UI) DangerouslyImage(node client.UiNode, class string) template.HTML {
	dangerouslyImage := &DangerouslyImage{
		Class: class,
	}

	if attributes := node.Attributes.UiNodeImageAttributes; attributes != nil {
		dangerouslyImage.Src = attributes.GetSrc()
		dangerouslyImage.Width = attributes.GetWidth()
		dangerouslyImage.Height = attributes.GetHeight()
		dangerouslyImage.Alt = t.GetNodeLabel(node)
	}

	serialized, _ := xml.Marshal(dangerouslyImage)

	return template.HTML(serialized)
}
