package controller

import (
	"Golang/dto"
	"encoding/json"
	"fmt"
	"github.com/sirupsen/logrus"
	"net/http"
)

type Controller interface {
	Invoke(res http.ResponseWriter, req *http.Request)
	GetPath() string
	GetMethods() []string
}

type AbstractController struct {
	Controller
}

func (t *AbstractController) fail(err error, res http.ResponseWriter) {
	logrus.WithError(err).Errorf("%+v", err)
	handleResponse := &dto.HandleResponse{
		Error: fmt.Sprintf("%+v", err),
		Success: false,
	}
	res.WriteHeader(http.StatusInternalServerError)
	if err := json.NewEncoder(res).Encode(handleResponse); err != nil {
		_, _ = res.Write([]byte(err.Error()))
	}
}
