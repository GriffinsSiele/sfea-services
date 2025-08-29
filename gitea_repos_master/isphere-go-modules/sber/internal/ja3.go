package internal

import (
	"crypto/rand"
	"encoding/csv"
	"errors"
	"fmt"
	"io"
	"math/big"
	"os"
)

type Ja3Loader struct {
	items []*Ja3
}

func NewJa3Loader() (*Ja3Loader, error) {
	fh, err := os.Open("resources/ja3.csv")
	if err != nil {
		return nil, fmt.Errorf("failed to open ja3.csv: %v", err)
	}
	//goland:noinspection GoUnhandledErrorResult
	defer fh.Close()

	csvReader := csv.NewReader(fh)
	csvReader.FieldsPerRecord = 3

	l := &Ja3Loader{
		items: make([]*Ja3, 0),
	}

	for {
		cells, err := csvReader.Read()
		if err != nil {
			if errors.Is(err, io.EOF) {
				break
			}
			return nil, fmt.Errorf("failed to read csv: %w", err)
		}

		l.items = append(l.items, &Ja3{
			UserAgent: cells[0],
			Ja3Str:    cells[2],
		})
	}

	return l, nil
}

func (l *Ja3Loader) Random() *Ja3 {
	index, _ := rand.Int(rand.Reader, big.NewInt(int64(len(l.items))))
	return l.items[index.Int64()]
}

type Ja3 struct {
	UserAgent string
	Ja3Str    string
}
