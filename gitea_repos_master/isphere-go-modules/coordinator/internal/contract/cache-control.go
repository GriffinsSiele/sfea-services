package contract

import "regexp"

type CacheControlValue string

const (
	CacheControl string = "Cache-Control"
)

const (
	CacheControlNoCache      CacheControlValue = "no-cache"
	CacheControlNoStore      CacheControlValue = "no-store"
	CacheControlOnlyIfCached CacheControlValue = "only-if-cached"
	CacheControlMaxAge       CacheControlValue = "max-age"
)

var MatchCacheControlMaxAge = regexp.MustCompile(`(?m)max-age=(\d+)`)
