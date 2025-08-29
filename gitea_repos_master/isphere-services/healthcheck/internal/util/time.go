package util

import "time"

func MustDuration(v string) time.Duration {
	d, _ := time.ParseDuration(v)
	return d
}
