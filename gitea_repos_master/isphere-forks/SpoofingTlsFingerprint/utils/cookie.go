package utils

import (
	"github.com/Danny-Dasilva/CycleTLS/cycletls"
	"net/http"
	"strings"
)

type CookieUtil struct {
}

func (t *CookieUtil) UnescapeDelimiter(cookie string) string {
	return strings.ReplaceAll(cookie, "/,/", ", ")
}

func (t *CookieUtil) StringToList(cookie string) ([]*cycletls.Cookie, error) {
	header := http.Header{}
	cookies := strings.Split(cookie, "/,/")
	for _, cook := range cookies {
		header.Add("Set-Cookie", cook)
	}
	req := http.Response{Header: header}
	res := make([]*cycletls.Cookie, 0, len(req.Cookies()))
	for _, cook := range req.Cookies() {
		res = append(res, &cycletls.Cookie{
			Name:     cook.Name,
			Value:    cook.Value,
			Path:     cook.Path,
			Domain:   cook.Domain,
			Expires:  cook.Expires,
			MaxAge:   cook.MaxAge,
			Secure:   cook.Secure,
			HTTPOnly: cook.HttpOnly,
		})
	}
	return res, nil
}

func NewCookieUtil() *CookieUtil {
	return &CookieUtil{}
}
