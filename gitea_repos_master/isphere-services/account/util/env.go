package util

import "os"

func GetenvBool(key string) bool {
	return os.Getenv(key) == "true"
}
