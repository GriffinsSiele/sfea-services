package model

type Supplement struct {
	LicenseFK        string            `xml:"licenseFK" json:"license_fk"`
	Number           string            `xml:"number" json:"number"`
	StatusName       string            `xml:"statusName" json:"status_name"`
	SchoolGUID       string            `xml:"schoolGuid" json:"school_guid"`
	SchoolName       string            `xml:"schoolName" json:"school_name"`
	ShortName        string            `xml:"shortName" json:"short_name"`
	LawAddress       string            `xml:"lawAddress" json:"law_address"`
	OrgName          string            `xml:"orgName" json:"org_name"`
	NumLicDoc        string            `xml:"numLicDoc" json:"num_lic_doc"`
	DateLicDoc       *Date             `xml:"dateLicDoc" json:"date_lic_doc"`
	SysGUID          string            `xml:"sysGuid" json:"sys_guid"`
	LicensedPrograms *LicensedPrograms `xml:"licensedPrograms" json:"-"`
}
