package model

import "encoding/json"

type LicensedPrograms struct {
	LicensedProgram []*LicensedProgram `xml:"licensedProgram"`
}

func (t *LicensedPrograms) MarshalJSON() ([]byte, error) {
	return json.Marshal(t.LicensedProgram)
}
