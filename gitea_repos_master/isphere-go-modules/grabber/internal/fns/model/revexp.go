package model

import (
	"encoding/xml"

	"git.i-sphere.ru/isphere-go-modules/grabber/internal/fns"
	"github.com/google/uuid"
)

type Revexp struct {
	XMLName       xml.Name `xml:"Файл"`
	ID            string   `xml:"ИдФайл,attr"`
	FormVersion   string   `xml:"ВерсФорм,attr"`
	AppVersion    string   `xml:"ВерсПрог,attr"`
	Type          string   `xml:"ТипИнф,attr"`
	SenderDetails *struct {
		ResponsiblePerson *struct {
			Surname string `xml:"Фамилия,attr"`
			Name    string `xml:"Имя,attr"`
		} `xml:"ФИООтв"`
	} `xml:"ИдОтпр"`
	DocumentsCount int               `xml:"КолДок,attr"`
	Documents      []*RevexpDocument `xml:"Документ"`
}

type RevexpDocument struct {
	ID        uuid.UUID `xml:"ИдДок,attr"`
	CreatedAt fns.Date  `xml:"ДатаДок,attr"`
	UpdatedAt fns.Date  `xml:"ДатаСост,attr"`
	Subject   *struct {
		Name string `xml:"НаимОрг,attr"`
		INN  string `xml:"ИННЮЛ,attr"`
	} `xml:"СведНП"`
	Underpayments []*struct {
		Income  float64 `xml:"СумДоход,attr" json:"income"`
		Expense float64 `xml:"СумРасход,attr" json:"expense"`
	} `xml:"СведДохРасх"`
}
