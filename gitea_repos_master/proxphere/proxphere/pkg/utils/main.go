package utils

import (
	"crypto/md5"
	"crypto/rand"
	"encoding/hex"
	"fmt"
	"math/big"
	"strconv"
	"time"
)

func Coalesce[T comparable](elems ...T) T {
	var empty T
	for _, elem := range elems {
		if elem != empty {
			return elem
		}
	}
	return empty
}

func If[T any](cond bool, a, b T) T {
	if cond {
		return a
	}
	return b
}

func IfB(cond bool) bool {
	return If[bool](cond, true, false)
}

func Must(args ...any) error {
	if err, ok := args[len(args)-1].(error); ok && err != nil {
		return err
	}
	return nil
}

func Ptr[T any](v T) *T {
	return &v
}

func Random[T any](elems []T) (T, error) {
	var empty T
	if len(elems) == 0 {
		return empty, fmt.Errorf("no elements in slice: %v", empty)
	}
	index, err := rand.Int(rand.Reader, big.NewInt(int64(len(elems))))
	if err != nil {
		return empty, fmt.Errorf("failed to generate random index: %w", err)
	}
	return elems[index.Int64()], nil
}

func Shuffle[T any](slice []T) error {
	for i := len(slice) - 1; i > 0; i-- {
		index, err := rand.Int(rand.Reader, big.NewInt(int64(i+1)))
		if err != nil {
			return fmt.Errorf("failed to generate random index: %w", err)
		}
		slice[i], slice[index.Int64()] = slice[index.Int64()], slice[i]
	}
	return nil
}

func FirstParamAsInt(params ...string) int {
	if len(params) == 0 {
		return 0
	}
	res, err := strconv.Atoi(params[0])
	if err != nil {
		return 0
	}
	return res
}

func SecondParamAsDuration(params ...string) time.Duration {
	if len(params) < 2 {
		return 0
	}
	res, err := time.ParseDuration(params[1])
	if err != nil {
		return 0
	}
	return res
}

func FirstParamAsIntWithMax(max int, params ...string) int {
	res := FirstParamAsInt(params...)
	if res > max {
		return max
	}
	return res
}

func IntSliceToStringSlice(slice []int) []string {
	res := make([]string, len(slice))
	for i, v := range slice {
		res[i] = strconv.Itoa(v)
	}
	return res
}

func MustInt(s string) int {
	res, err := strconv.Atoi(s)
	if err != nil {
		panic(err)
	}
	return res
}

func MD5Hash(input string) string {
	hash := md5.New()
	hash.Write([]byte(input))
	hashBytes := hash.Sum(nil)
	return hex.EncodeToString(hashBytes)
}
