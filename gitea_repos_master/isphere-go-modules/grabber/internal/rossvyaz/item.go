package rossvyaz

import (
	"fmt"
	"strconv"
	"strings"

	"git.i-sphere.ru/isphere-go-modules/grabber/internal/client"
)

type Item struct {
	ABCDEF          uint16
	PhonePoolStart  uint32
	PhonePoolEnd    uint32
	PhonePoolSize   uint16
	Operator        string
	PhoneRegionName string
	Region1         string
	Region2         string
	Region3         string
	RegionCode      string
}

var replacement = map[string]string{
	" обл.":         " область",
	"город ":        "",
	"город-курорт ": "",
	"ЗАТО ":         "",
}

func NewItemUsingRow(row []string, regions *client.Regions) (*Item, error) {
	t := &Item{}

	if abcdef, err := strconv.ParseUint(row[0], 10, 16); err != nil {
		return nil, fmt.Errorf("failed to parse abcdef: %w", err)
	} else {
		t.ABCDEF = uint16(abcdef)
	}

	if phonePoolStart, err := strconv.ParseUint(row[1], 10, 32); err != nil {
		return nil, fmt.Errorf("failed to parse phone pool start: %w", err)
	} else {
		t.PhonePoolStart = uint32(phonePoolStart)
	}

	if phonePoolEnd, err := strconv.ParseUint(row[2], 10, 32); err != nil {
		return nil, fmt.Errorf("failed to parse phone pool end: %w", err)
	} else {
		t.PhonePoolEnd = uint32(phonePoolEnd)
	}

	if phonePoolSize, err := strconv.ParseUint(row[3], 10, 32); err != nil {
		return nil, fmt.Errorf("failed to parse phone pool size: %w", err)
	} else {
		t.PhonePoolSize = uint16(phonePoolSize)
	}

	geo := strings.Split(row[5], "|")
	for i, j := 0, len(geo)-1; i < j; i, j = i+1, j-1 {
		geo[i], geo[j] = geo[j], geo[i]
	}

	for old, newStr := range replacement {
		geo[0] = strings.ReplaceAll(geo[0], old, newStr)
	}

region:
	for _, region := range regions.DictionariesRegions {
		for _, name := range region.Names {
			if name == geo[0] ||
				strings.Contains(name, geo[0]) ||
				strings.Contains(geo[0], name) {
				t.RegionCode = region.Code

				break region
			}
		}
	}

	t.Operator = row[4]
	t.PhoneRegionName = row[5]
	t.Region1 = geo[0]

	if len(geo) > 1 {
		t.Region2 = geo[1]

		if len(geo) > 2 {
			t.Region3 = geo[2]
		}
	}

	return t, nil
}
