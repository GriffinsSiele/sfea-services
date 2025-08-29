package contract

import (
	"crypto/tls"
	"slices"
)

var SupportedVersions = []uint16{
	tls.VersionTLS10,
	tls.VersionTLS11,
	tls.VersionTLS12,
	tls.VersionTLS13,
}

func SupportedVersion(version uint16) bool {
	return slices.Contains(SupportedVersions, version)
}
