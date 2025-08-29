package handler

import (
	"fmt"
	"net/http"

	"git.i-sphere.ru/isphere-go-modules/hlr/internal/dto"
	"github.com/gin-gonic/gin"
)

func Callback(c *gin.Context) {
	var msg dto.CallbackReq

	if err := c.ShouldBindJSON(&msg); err != nil {
		_ = c.AbortWithError(http.StatusBadRequest, fmt.Errorf("failed to bind input: %w", err))

		return
	}

	var statusCode int
	if msg.StatusCompleted() {
		statusCode = http.StatusOK
	} else {
		statusCode = http.StatusAccepted
	}

	c.JSON(statusCode, &Response{Data: []*dto.CallbackReq{&msg}})
}

type Response struct {
	Data []*dto.CallbackReq `json:"data"`
}
