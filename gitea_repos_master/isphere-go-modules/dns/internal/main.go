package internal

import (
	"fmt"
)

func ErrEnvFile(file string) error {
	return fmt.Errorf("failed to load %s environment file", file)
}
