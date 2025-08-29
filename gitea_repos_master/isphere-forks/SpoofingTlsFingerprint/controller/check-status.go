package controller

import (
	"encoding/json"
	"net/http"
)

type CheckStatusController struct{
	AbstractController
}

func (t *CheckStatusController) GetPath() string {
	return "/check-status"
}

func (t *CheckStatusController) GetMethods() []string {
	return []string{"GET"}
}

func (t *CheckStatusController) Invoke(res http.ResponseWriter, req *http.Request) {
	res.Header().Set("Content-Type", "application/json")
	if err := json.NewEncoder(res).Encode("good"); err != nil {
		t.fail(err, res)
	}
}

func NewCheckStatusController() *CheckStatusController {
	return &CheckStatusController{}
}
