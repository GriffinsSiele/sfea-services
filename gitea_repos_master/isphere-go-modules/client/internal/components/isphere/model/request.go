package model

import (
	"encoding/xml"
	"net"
)

type Request struct {
	XMLName     xml.Name         `xml:"Request"`
	UserIP      net.IP           `xml:"UserIP"`
	UserID      string           `xml:"UserID"`
	Password    string           `xml:"Password"`
	RequestID   string           `xml:"requestId,omitempty"`
	RequestType string           `xml:"requestType"`
	Sources     JoinedStrings    `xml:"sources"`
	Rules       JoinedStrings    `xml:"rules,omitempty"`
	Timeout     TimeoutInSeconds `xml:"timeout,omitempty"`
	Recursive   BoolAsInt        `xml:"recursive"`
	Async       BoolAsInt        `xml:"async"`
	PersonReq   []PersonReq      `xml:"PersonReq,omitempty"`
	PhoneReq    []PhoneReq       `xml:"PhoneReq,omitempty"`
	EmailReq    []EmailReq       `xml:"EmailReq,omitempty"`
}

type PersonReq struct {
	XMLName              xml.Name  `xml:"PersonReq"`
	Surname              string    `xml:"paternal,omitempty"`
	Name                 string    `xml:"first,omitempty"`
	Patronymic           string    `xml:"middle,omitempty"`
	Birthday             *DateOnly `xml:"birthDt,omitempty"`
	PassportSeries       string    `xml:"passport_series,omitempty"`
	PassportNumber       string    `xml:"passport_number,omitempty"`
	PassportIssueAt      *DateOnly `xml:"issueDate,omitempty"`
	INN                  string    `xml:"inn,omitempty"`
	SNILS                string    `xml:"snils,omitempty"`
	DriverLicenseNumber  string    `xml:"driver_number,omitempty"`
	DriverLicenseIssueAt *DateOnly `xml:"driver_date,omitempty"`
	RegionID             string    `xml:"region_id,omitempty"`
	Date                 *DateOnly `xml:"reqdate,omitempty"`
}

type PhoneReq struct {
	XMLName xml.Name `xml:"PhoneReq"`
	Phone   string   `xml:"phone"`
}

type EmailReq struct {
	XMLName xml.Name `xml:"EmailReq"`
	Email   string   `xml:"email"`
}
