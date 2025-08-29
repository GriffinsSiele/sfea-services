package util_test

import (
	"testing"

	"gitea-http.gitea.svc.cluster.local/isphere-go-modules/coordinator/internal/util"
	"github.com/stretchr/testify/assert"
)

func TestSliceContains(t *testing.T) {
	t.Parallel()

	assert.True(t, util.SliceContains([]string{"a", "b"}, "a"))
	assert.True(t, util.SliceContains([]int{0, 1, 2}, 0))
	assert.False(t, util.SliceContains([]string{"a"}, "b"))
	assert.False(t, util.SliceContains([]int{0, 1}, 2))
}
