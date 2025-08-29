package controller

import (
	"Golang/dto"
	"Golang/utils"
	"encoding/base64"
	"encoding/json"
	"github.com/Danny-Dasilva/CycleTLS/cycletls"
	"net/http"
	"strings"
)

type HandleController struct {
	AbstractController
	cookieUtil *utils.CookieUtil
}

func (t *HandleController) GetPath() string {
	return "/handle"
}

func (t *HandleController) GetMethods() []string {
	return []string{"POST"}
}

func (t *HandleController) Invoke(res http.ResponseWriter, req *http.Request) {
	res.Header().Set("Content-Type", "application/json")

	var handleRequest dto.HandleRequest
	if err := json.NewDecoder(req.Body).Decode(&handleRequest); err != nil {
		t.fail(err, res)
		return
	}
	client := cycletls.Init()

	resp, err := client.Do(handleRequest.Url, cycletls.Options{
		Body:            handleRequest.Body,
		Proxy:           handleRequest.Proxy,
		Timeout:         handleRequest.Timeout,
		Headers:         handleRequest.Headers,
		Ja3:             handleRequest.Ja3,
		UserAgent:       handleRequest.UserAgent,
		DisableRedirect: handleRequest.DisableRedirect,
		Cookies:         handleRequest.Cookies,
	}, handleRequest.Method)

	var handleResponse dto.HandleResponse

	if err != nil {
		handleResponse.Success = false
		handleResponse.Error = err.Error()
		if err = json.NewEncoder(res).Encode(handleResponse); err != nil {
			t.fail(err, res)
		}
		return
	}

	response, err := t.decodeResponse(&resp)
	if err != nil {
		t.fail(err, res)
		return
	}
	handleResponse.Success = true
	handleResponse.Payload = &dto.HandleResponsePayload{
		Text:       response,
		Headers:    resp.Headers,
		HeadersISO: t.convertHeadersToISO(resp.Headers),
		Status:     resp.Status,
		Url:        handleRequest.Url,
	}

	if cookie, ok := handleResponse.Payload.Headers["Set-Cookie"]; ok {
		if handleResponse.Payload.Cookies, err = t.cookieUtil.StringToList(cookie); err != nil {
			t.fail(err, res)
			return
		}
		handleResponse.Payload.Headers["Set-Cookie"] = t.cookieUtil.UnescapeDelimiter(cookie)
	}

	if err = json.NewEncoder(res).Encode(handleResponse); err != nil {
		t.fail(err, res)
	}
}

func (t *HandleController) convertHeadersToISO(headers map[string]string) map[string][]string {
	res := map[string][]string{}
	for key, value := range headers {
		if _, ok := res[key]; !ok {
			res[key] = make([]string, 0)
		}
		values := strings.Split(value, "/,/")
		for _, value := range values {
			res[key] = append(res[key], value)
		}
	}
	return res
}

func (t *HandleController) decodeResponse(res *cycletls.Response) (string, error) {
	var result string
	switch res.Headers["Content-Encoding"] {
	case "gzip", "deflate", "br":
		if strings.HasPrefix(res.Headers["Content-Type"], "image/") ||
			strings.Contains(res.Headers["Content-Type"], "/pdf") {
			result = base64.StdEncoding.EncodeToString([]byte(res.Body))
		} else {
			result = res.Body
		}
	default:
		result = res.Body
	}
	return result, nil
}

func NewHandleController(cookieUtil *utils.CookieUtil) *HandleController {
	return &HandleController{
		cookieUtil: cookieUtil,
	}
}
