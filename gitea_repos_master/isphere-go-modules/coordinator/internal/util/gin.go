package util

import (
	"strings"

	"github.com/gin-gonic/gin"
)

func Marshal(c *gin.Context, code int, subj any) {
	if !strings.Contains(c.GetHeader("Accept"), "application/x-yaml") {
		c.JSON(code, subj)
	} else {
		c.YAML(code, subj)
	}
}
