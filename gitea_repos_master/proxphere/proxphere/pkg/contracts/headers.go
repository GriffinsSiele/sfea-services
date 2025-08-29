package contracts

import "strings"

type Header string

const (
	XRequestID                  Header = "X-Request-Id"
	XSphereJA3                  Header = "X-Sphere-JA3"
	XSphereProxySpecCountryCode Header = "X-Sphere-Proxy-Spec-Country-Code"
	XSphereProxySpecDisable     Header = "X-Sphere-Proxy-Spec-Disable"
	XSphereProxySpecGroupID     Header = "X-Sphere-Proxy-Spec-Group-Id"
	XSphereProxySpecID          Header = "X-Sphere-Proxy-Spec-Id"
	XSphereProxySpecStrategy    Header = "X-Sphere-Proxy-Spec-Strategy"
	XSphereProxySpecStrategyTTL Header = "X-Sphere-Proxy-Spec-Strategy-Ttl"
	XSphereProxySpecTTL         Header = "X-Sphere-Proxy-Spec-Ttl"
	XSphereVerbose              Header = "X-Sphere-Verbose"
)

func IsSpecialHeaderName(name string) bool {
	for _, special := range allSpecialHeaders() {
		if strings.ToLower(name) == strings.ToLower(string(special)) {
			return true
		}
	}
	return false
}

func allSpecialHeaders() []Header {
	return []Header{
		XRequestID,
		XSphereJA3,
		XSphereProxySpecCountryCode,
		XSphereProxySpecDisable,
		XSphereProxySpecGroupID,
		XSphereProxySpecID,
		XSphereProxySpecStrategy,
		XSphereProxySpecStrategyTTL,
		XSphereProxySpecTTL,
		XSphereVerbose,
	}
}
