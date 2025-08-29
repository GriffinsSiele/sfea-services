package internal

import (
	"errors"
	"fmt"
)

func ErrUnexpectedResponse() error {
	return errors.New("unexpected response")
}

func ErrEnvFile(file string) error {
	return fmt.Errorf("failed to load %s environment file", file)
}
