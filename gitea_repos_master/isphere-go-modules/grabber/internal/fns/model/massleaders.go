package model

type Massleader struct {
	INN        string `mapstructure:"G1"`
	Surname    string `mapstructure:"G2"`
	Name       string `mapstructure:"G3"`
	Patronymic string `mapstructure:"G4"`
	Count      string `mapstructure:"G5"`
}
