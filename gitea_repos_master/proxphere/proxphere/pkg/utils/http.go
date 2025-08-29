package utils

import (
	"strings"

	http "github.com/Danny-Dasilva/fhttp"
	"go.i-sphere.ru/proxy/pkg/contracts"
)

func RequestParam(req *http.Request, name contracts.Header) string {
	cookie, err := req.Cookie(string(name))
	if err == nil {
		return cookie.Value
	}

	return req.Header.Get(string(name))
}

func CleanRequest(req *http.Request) *http.Request {
	clone := req.Clone(req.Context())

	// clean headers
	newHeaders := make(http.Header)
	for header, headerValues := range clone.Header {
		if strings.HasPrefix(strings.ToLower(header), "x-sphere") {
			continue
		}
		for _, h := range headerValues {
			newHeaders.Add(header, h)
		}
	}
	clone.Header = newHeaders

	// clean cookies
	cookiesLine := clone.Header.Get("Cookie")
	cookies := strings.Split(cookiesLine, ";")
	newCookies := make([]string, 0, len(cookies))
	for _, cookie := range cookies {
		if strings.HasPrefix(strings.ToLower(cookie), "x-sphere") {
			continue
		}
		newCookie := strings.TrimSpace(cookie)
		if newCookie != "" {
			newCookies = append(newCookies, strings.TrimSpace(cookie))
		}
	}
	if len(newCookies) == 0 {
		clone.Header.Del("Cookie")
	} else {
		clone.Header.Set("Cookie", strings.Join(newCookies, "; "))
	}

	return clone
}

// Deprecated: Not need now
func UnpackResponse(resp *http.Response) (*http.Response, error) {
	return resp, nil
}
