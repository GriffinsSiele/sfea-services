package client

import (
	"net/http"

	"git.i-sphere.ru/isphere-go-modules/framework/pkg/contract"
	"github.com/motemen/go-loghttp"
)

func NewHTTPClient() contract.HTTPClient {
	return &http.Client{
		Transport: &loghttp.Transport{},
	}
}
