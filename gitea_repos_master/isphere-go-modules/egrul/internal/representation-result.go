package internal

import "time"

type RepresentationResult struct {
	OGRN               string     `json:"OGRN,omitempty"`               // ОГРН
	Name               string     `json:"Name,omitempty"`               // Наименование
	FullName           string     `json:"FullName,omitempty"`           // Полное наименование
	Head               string     `json:"Head,omitempty"`               // Руководитель
	RegDate            *time.Time `json:"RegDate,omitempty"`            // Дата регистрации
	Type               string     `json:"Type,omitempty"`               // Тип записи
	IPType             string     `json:"IPType,omitempty"`             // Тип ИП
	IPName             string     `json:"IPName,omitempty"`             // Имя ИП
	OKPO               string     `json:"OKPO,omitempty"`               // ОКПО
	OKATO              string     `json:"OKATO,omitempty"`              // ОКАТО
	CloseDate          string     `json:"CloseDate,omitempty"`          // Дата прекращения деятельности
	FNS                string     `json:"FNS,omitempty"`                // Подразделение ФНС
	FNSDate            string     `json:"FNSDate,omitempty"`            // Дата постановки на учет в ФНС
	PFRNum             string     `json:"PFRNum,omitempty"`             // Номер ПФР
	PFRDate            string     `json:"PFRDate,omitempty"`            // Дата регистрации в ПФР
	OKVED              string     `json:"OKVED,omitempty"`              // Основной код ОКВЭД
	OKVEDName          string     `json:"OKVEDName,omitempty"`          // Основной вид деятельности
	IPStatus           string     `json:"IPStatus,omitempty"`           // Статус
	OKTMO              string     `json:"OKТМO,omitempty"`              // ОКТМО
	FSSNum             string     `json:"FSSNumm,omitempty"`            // Номер ФСС
	FSSDate            string     `json:"FSSDate,omitempty"`            // Дата регистрации в ФСС
	KPP                string     `json:"KPP,omitempty"`                // КПП
	Capital            string     `json:"Capital,omitempty"`            // Уставный капитал
	HeadTitle          string     `json:"HeadTitle,omitempty"`          // Должность руководителя
	HeadINN            string     `json:"HeadINN,omitempty"`            // ИНН руководителя
	HeadDate           string     `json:"HeadDate,omitempty"`           // Дата вступления в должность
	Owner              string     `json:"Owner,omitempty"`              // Владелец
	OwnerINN           string     `json:"OwnerINN,omitempty"`           // ИНН владельца
	OwnerPercent       string     `json:"OwnerPercent,omitempty"`       // Доля %
	OwnerTotal         string     `json:"OwnerTotal,omitempty"`         // Стоимость доли
	OwnerDate          string     `json:"OwnerDate,omitempty"`          // Дата начала владения
	UnrelialbleAddress string     `json:"UnrelialbleAddress,omitempty"` // Недостоверный адрес
	OKOGU              string     `json:"OKOGU,omitempty"`              // ОКОГУ
	OKOPF              string     `json:"OKOPF,omitempty"`              // ОКОПФ
	OKFS               string     `json:"OKFS,omitempty"`               // ОКФС
	Location           string     `json:"Location,omitempty"`           // Местоположение
	AddressDate        string     `json:"AddressDate,omitempty"`        // Дата изменения адреса
}
