package util

import "io"

type ProgressDecorator struct {
	io.Reader
	OnProgress ProgressFunc
}

type ProgressFunc func(n int64)

func (t *ProgressDecorator) Read(p []byte) (int, error) {
	n, err := t.Reader.Read(p)

	t.OnProgress(int64(n))

	return n, err
}
