package contract

import (
	"errors"
	"fmt"
	"strings"
)

var (
	ErrNotFound   = errNotFound()
	ErrValidation = errValidation()
)

func errNotFound() error {
	return errors.New("not found")
}

func errValidation() error {
	return errors.New("validation error")
}

// ---

type NotFoundError struct {
	Op  string
	Key string
	Err error
}

func (t *NotFoundError) Error() string {
	return fmt.Sprintf("%s: %v", t.Key, t.Err)
}

// ---

type ValidationError struct {
	Violations []*Violation
	Err        error
}

type Violation struct {
	PropertyPath string
	Messages     []string
}

func (t *ValidationError) Error() string {
	messages := make([]string, 0, len(t.Violations))
	for _, violation := range t.Violations {
		messages = append(messages, fmt.Sprintf("%s: %s", violation.PropertyPath, strings.Join(violation.Messages, ", ")))
	}

	return fmt.Sprintf("%s: %v", strings.Join(messages, "; "), t.Err)
}
