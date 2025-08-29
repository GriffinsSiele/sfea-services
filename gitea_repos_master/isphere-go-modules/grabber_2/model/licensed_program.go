package model

type LicensedProgram struct {
	SupplementFK      string `xml:"supplementFk"`
	EduProgramType    string `xml:"eduProgramType"`
	Code              string `xml:"code"`
	Name              string `xml:"name"`
	EduLevelName      string `xml:"eduLevelName"`
	EduProgramKind    string `xml:"eduProgramKind"`
	QualificationCode string `xml:"qualificationCode"`
	QualificationName string `xml:"qualificationName"`
	SysGUID           string `xml:"sysGuid"`
}
