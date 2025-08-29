package util

import (
	"math/rand"
	"time"
)

const letterBytes = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"

func RandomSlice[T any](slice []T) T {
	var (
		randomSource = rand.NewSource(time.Now().Unix())
		random       = rand.New(randomSource)
		randomIndex  = random.Intn(len(slice))
	)

	return slice[randomIndex]
}

func RandomString(n uint8) string {
	b := make([]byte, n)
	for i := range b {
		b[i] = letterBytes[rand.Int63()%int64(len(letterBytes))]
	}

	return string(b)
}
