package model

type Masaddress struct {
	Region     string `mapstructure:"G1"`
	District   string `mapstructure:"G2"`
	City       string `mapstructure:"G3"`
	Settlement string `mapstructure:"G4"`
	Street     string `mapstructure:"G5"`
	House      string `mapstructure:"G6"`
	Building   string `mapstructure:"G7"`
	Apartment  string `mapstructure:"G8"`
	Count      string `mapstructure:"G9"`
}
