package hacking_test

import (
	"encoding/json"
	"strings"
	"testing"

	"gitea-http.gitea.svc.cluster.local/isphere-go-modules/coordinator/internal/hacking"
	"github.com/ake-persson/mapslice-json"
	"github.com/davecgh/go-spew/spew"
	"github.com/stretchr/testify/assert"
	"gopkg.in/yaml.v2"
)

func TestCastMapSliceAsRecords(t *testing.T) {
	t.Parallel()

	// language=yaml
	inputYAML := `- field1: "value1"
  field2: 2
  field3:
    field4: "value4"
  field5:
    - field6: "value 6 1"
    - field6: "value 6 2"
`
	var vYAML []yaml.MapSlice

	assert.NoError(t, yaml.NewDecoder(strings.NewReader(inputYAML)).Decode(&vYAML))

	recordsYAML, err := hacking.CastMapSliceAsRecordsYAML(&vYAML)
	assert.NoError(t, err)
	spew.Dump(recordsYAML...)

	// language=json
	inputJSON := `[
  {
    "field1": "value1",
    "field2": 2,
    "field3": {
      "field4": "value4"
    },
    "field5": [
      {
        "field6": "value 6 1"
      },
      {
        "field6": "value 6 2"
      }
    ]
  }
]
`
	var vJSON []mapslice.MapSlice

	assert.NoError(t, json.NewDecoder(strings.NewReader(inputJSON)).Decode(&vJSON))

	recordsJSON, err := hacking.CastMapSliceAsRecordsJSON(&vJSON)
	assert.NoError(t, err)
	spew.Dump(recordsJSON...)
}
