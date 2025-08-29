package x

import (
	"crypto/md5"
	"encoding/hex"
)

// Coalesce return `a` or `b` if `a` is empty
func Coalesce[T comparable](a, b T) T {
	var empty T
	if a == empty {
		return b
	}
	return a
}

func MD5Hash(text string) string {
	hash := md5.Sum([]byte(text))
	return hex.EncodeToString(hash[:])
}

// Wrap return a string with suffix and prefix
func Wrap(s, a, b string) string {
	return a + s + b
}

// WrapB return a string with border string
func WrapB(s, a string) string {
	return Wrap(s, a, a)
}

// WrapQ return a string with quotes
func WrapQ(s string) string {
	return WrapB(s, `"`)
}

func Ptr[T comparable](v T) *T {
	var empty T
	if v == empty {
		return nil
	}
	return &v
}

func Value[T comparable](v *T) T {
	if v == nil {
		var empty T
		return empty
	}
	return *v
}
