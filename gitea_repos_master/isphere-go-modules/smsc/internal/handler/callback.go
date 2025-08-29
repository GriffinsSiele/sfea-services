package handler

import (
	"fmt"
	"net/http"

	"git.i-sphere.ru/isphere-go-modules/ripe/internal/dto"
	"github.com/gin-gonic/gin"
)

func Callback(c *gin.Context) {
	var msg dto.CallbackReq

	if err := c.ShouldBindJSON(&msg); err != nil {
		_ = c.AbortWithError(http.StatusBadRequest, fmt.Errorf("failed to bind input: %w", err))

		return
	}

	c.Writer.Header().Set("X-Message-ID", c.GetHeader("X-Message-ID"))
	c.JSON(http.StatusOK, &Response{Data: []*dto.CallbackReq{&msg}})
}

type Response struct {
	Data []*dto.CallbackReq `json:"data"`
}
