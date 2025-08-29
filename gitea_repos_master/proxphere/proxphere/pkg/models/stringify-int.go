package models

import (
	"strconv"
)

type StringifyInt int

func (i StringifyInt) String() string {
	return strconv.Itoa(int(i))
}
