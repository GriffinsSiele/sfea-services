package util

import (
	"math/rand"
	"time"
)

func RandomSlice[T any](slice []T) T {
	var (
		randomSource = rand.NewSource(time.Now().Unix())
		random       = rand.New(randomSource)
		randomIndex  = random.Intn(len(slice))
	)

	return slice[randomIndex]
}

func SliceContains[T comparable](slice []T, expected T) bool {
	for _, elem := range slice {
		if elem == expected {
			return true
		}
	}

	return false
}

func Ptr[T any](v T) *T {
	return &v
}

func PtrVal[T any](v *T) T {
	return *v
}
