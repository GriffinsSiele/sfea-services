package internal_test

import (
	"egrul/internal"
	"testing"

	"github.com/stretchr/testify/assert"
	"gopkg.in/yaml.v3"
)

func TestHierarchyBuilder_Build(t *testing.T) {
	t.Parallel()

	builder := internal.NewHierarchyBuilder()

	result, err := builder.BuildWithTemplate(
		// language=yaml
		mustHierarchyTemplate(t, `
title1:
  title2: ~
  title3: ~
title4:
  title5: ~
title6:
  "{number}": ~
title7: ~
`),
		// language=yaml
		mustNodes(t, `
- { type: section, title: title1 }
- { type: section, title: title2 }
- { type: record, title: param1, value: value1 }
- { type: record, title: param2, value: value2 }
- { type: section, title: title3 }
- { type: record, title: param3, value: value3 }
- { type: record, title: param4, value: value4 }
- { type: section, title: title4 }
- { type: section, title: title5 }
- { type: record, title: param5, value: value5 }
- { type: record, title: param6, value: value6 }
- { type: section, title: title6 }
- { type: section, title: "1" }
- { type: record, title: param7, value: value7 }
- { type: record, title: param8, value: value8 }
- { type: section, title: "2" }
- { type: record, title: param9, value: value9 }
- { type: record, title: param10, value: value10 }
- { type: section, title: title7 }
- { type: record, title: param11, value: value11 }
- { type: record, title: param12, value: value12 }
`),
	)

	assert.NoError(t, err)

	// language=yaml
	assert.Equal(t, mustMap(t, `
title1:
  title2:
    param1: value1
    param2: value2
  title3:
    param3: value3
    param4: value4
title4:
  title5:
    param5: value5
    param6: value6
title6:
  "1":
    param7: value7
    param8: value8
  "2":
    param9: value9
    param10: value10
title7:
  param11: value11
  param12: value12
`), result)
}

func mustHierarchyTemplate(t *testing.T, serialized string) internal.HierarchyTemplate {
	var hierarchyTemplate internal.HierarchyTemplate
	err := yaml.Unmarshal([]byte(serialized), &hierarchyTemplate)
	assert.NoError(t, err)
	return hierarchyTemplate
}

func mustNodes(t *testing.T, serialized string) internal.Nodes {
	var nodes internal.Nodes
	err := yaml.Unmarshal([]byte(serialized), &nodes)
	assert.NoError(t, err)
	return nodes
}

func mustMap(t *testing.T, serialized string) map[string]any {
	data := map[string]any{}
	err := yaml.Unmarshal([]byte(serialized), &data)
	assert.NoError(t, err)
	return data
}
