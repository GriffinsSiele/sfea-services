package model

type License struct {
	SysGUID        string       `xml:"sysGuid"`
	SchoolGUID     string       `xml:"schoolGuid"`
	StatusName     string       `xml:"statusName" parquet:"name=status_name, convertedtype=UTF8, type=BYTE_ARRAY, encoding=PLAIN_DICTIONARY"`
	SchoolName     string       `xml:"schoolName" parquet:"name=school_name, convertedtype=UTF8, type=BYTE_ARRAY"`
	ShortName      string       `xml:"shortName"`
	INN            string       `xml:"Inn"`
	OGRN           string       `xml:"Ogrn"`
	SchoolTypeName string       `xml:"schoolTypeName"`
	LawAddress     string       `xml:"lawAddress"`
	OrgName        string       `xml:"orgName"`
	RegNum         string       `xml:"regNum" parquet:"name=reg_num, type=BYTE_ARRAY"`
	DateLicDoc     *Date        `xml:"dateLicDoc"`
	DateLicDocTime *int32       `parquet:"name=date_lic_doc, type=INT32, convertedtype=DATE, repetitiontype=OPTIONAL"`
	DateEnd        *Date        `xml:"dateEnd"`
	DateEndTime    *int32       `parquet:"name=date_end, type=INT32, convertedtype=DATE, repetitiontype=OPTIONAL"`
	Supplements    *Supplements `xml:"supplements"`
}
