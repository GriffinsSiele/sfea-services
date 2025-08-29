package middleware

import (
	"errors"
	"io"
)

type ProgressReader struct {
	reader   io.Reader
	reporter func(read int64)

	err error
}

func NewProgressReader(reader io.Reader, reporter ReporterFn) io.Reader {
	return &ProgressReader{
		reader:   reader,
		reporter: reporter,
	}
}

func (t *ProgressReader) Read(b []byte) (int, error) {
	if t.err != nil {
		return 0, t.err
	}

	n, err := t.reader.Read(b)
	if err != nil && !errors.Is(err, io.EOF) {
		t.err = err
	}

	t.reporter(int64(n))

	return n, err
}

// ---

type ReporterFn func(int64)
