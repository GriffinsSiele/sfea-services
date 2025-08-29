package internal

type Nodes []*Node

type Node struct {
	Type  NodeType `json:"type"`
	Title string   `json:"title"`
	Value *string  `json:"value,omitempty"`
}

type NodeType string

const (
	NodeTypeSection NodeType = "section"
	NodeTypeRecord  NodeType = "record"
)

type HierarchyTemplate map[string]HierarchyTemplate
