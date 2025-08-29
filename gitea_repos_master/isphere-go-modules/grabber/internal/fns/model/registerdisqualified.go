package model

import "git.i-sphere.ru/isphere-go-modules/grabber/internal/util"

type Registerdisqualified struct {
	ID                     int          `mapstructure:"G1"`
	FullName               string       `mapstructure:"G2"`
	Birthday               *util.Date   `mapstructure:"G3"`
	Birthplace             string       `mapstructure:"G4"`
	OrganizationName       string       `mapstructure:"G5"`
	OrganizationINN        string       `mapstructure:"G6"`
	OrganizationPosition   string       `mapstructure:"G7"`
	Reason                 string       `mapstructure:"G8"`
	ReasonIssuer           string       `mapstructure:"G9"`
	JudgeName              string       `mapstructure:"G10"`
	JudgePosition          string       `mapstructure:"G11"`
	DisqualificationPeriod *util.Period `mapstructure:"G12"`
	StartAt                *util.Date   `mapstructure:"G13"`
	ExpiredAt              *util.Date   `mapstructure:"G14"`
}
