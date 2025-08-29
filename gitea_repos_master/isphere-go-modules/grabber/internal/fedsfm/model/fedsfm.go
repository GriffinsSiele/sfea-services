package model

import "time"

type Fedsfm struct {
	Surname    string
	Name       string
	Patronymic *string
	Birthday   *time.Time
	Birthplace *string
}
