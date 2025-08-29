package model

type Disqualifiedperson struct {
	Name    string `mapstructure:"G1"`
	OGRN    string `mapstructure:"G2"`
	INN     string `mapstructure:"G3"`
	KPP     string `mapstructure:"G4"`
	Address string `mapstructure:"G5"`
}
