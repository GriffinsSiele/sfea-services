package internal

import (
	"strconv"

	"github.com/sirupsen/logrus"
)

type HierarchyBuilder struct {
}

func NewHierarchyBuilder() *HierarchyBuilder {
	return &HierarchyBuilder{}
}

func (h *HierarchyBuilder) BuildWithTemplate(template HierarchyTemplate, nodes Nodes) (map[string]any, error) {
	result := h.buildSkeletonWithTemplate(template)
	templateStack := []HierarchyTemplate{template}
	propertyPath := make([]string, 10)

	for _, node := range nodes {
		if node.Type == NodeTypeSection {
			var found bool
		CheckSection:
			for i := len(templateStack) - 1; i >= 0; i-- {
				for k, v := range templateStack[i] {
					if (k != "{number}" && k == node.Title) ||
						(k == "{number}" && h.isNumber(node.Title)) {
						templateStack = append(templateStack, v)
						propertyPath[i] = node.Title
						found = true
						break CheckSection
					}
				}
				if i > 0 {
					propertyPath[i] = ""
					templateStack = templateStack[:i]
				}
			}
			if !found {
				logrus.WithField("section", node.Title).Warn("section not found in template")
			}
		} else if node.Type == NodeTypeRecord && node.Value != nil {
			cur := result
			for _, property := range propertyPath {
				if property == "" {
					continue
				}
				if _, ok := cur[property]; !ok {
					cur[property] = make(map[string]any)
				}
				cur = cur[property].(map[string]any)
			}
			cur[node.Title] = *node.Value
		}
	}

	h.cleanMap(result)

	return result, nil
}

func (h *HierarchyBuilder) buildSkeletonWithTemplate(input HierarchyTemplate) map[string]any {
	result := map[string]any{}
	for k, v := range input {
		result[k] = h.buildSkeletonWithTemplate(v)
	}

	return result
}

func (h *HierarchyBuilder) cleanMap(m map[string]any) {
	var keysToDelete []string

	for k, v := range m {
		if k == "{number}" {
			keysToDelete = append(keysToDelete, k)
			continue
		}
		if v == nil {
			keysToDelete = append(keysToDelete, k)
			continue
		}
		if v2, ok := v.(map[string]any); ok {
			if len(v2) == 0 {
				keysToDelete = append(keysToDelete, k)
			} else {
				h.cleanMap(v2)
				if h.isEmpty(v2) {
					keysToDelete = append(keysToDelete, k)
				}
			}
		}
	}

	for _, k := range keysToDelete {
		delete(m, k)
	}
}

func (h *HierarchyBuilder) isNumber(v string) bool {
	_, err := strconv.Atoi(v)
	return err == nil
}

func (h *HierarchyBuilder) isEmpty(m map[string]any) bool {
	if m == nil {
		return true
	}
	if len(m) == 0 {
		return true
	}
	return false
}
