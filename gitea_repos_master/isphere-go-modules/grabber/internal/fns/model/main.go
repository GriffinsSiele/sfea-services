package model

import (
	"encoding/xml"

	"git.i-sphere.ru/isphere-go-modules/grabber/internal/util"
	"github.com/google/uuid"
)

type File[T Documenter] struct {
	XMLName xml.Name `xml:"Файл"`

	ID              string `xml:"ИдФайл,attr"`   // Идентификатор файла
	FormVersion     string `xml:"ВерсФорм,attr"` // Версия формата
	InformationType string `xml:"ТипИнф,attr"`   // Тип информации
	AppVersion      string `xml:"ВерсПрог,attr"` // Версия программы, с помощью которой сформирован файл
	DocumentsCount  int    `xml:"КолДок,attr"`   // Количество документов

	Sender    Sender `xml:"ИдОтпр"`   // Сведения об отправителе
	Documents []*T   `xml:"Документ"` // Состав и структура документа
}

type Sender struct {
	FullName *FullName `xml:"ФИООтв"`       // Фамилия, имя, отчество ответственного лица
	Position string    `xml:"ДолжОтв,attr"` // Должность ответственного лица
	Tel      string    `xml:"Тлф,attr"`     // Номер контактного телефона
	Email    string    `xml:"E-mail,attr"`  // E-mail
}

type FullName struct {
	Surname    string `xml:"Фамилия,attr"`  // Фамилия
	Name       string `xml:"Имя,attr"`      // Имя
	Patronymic string `xml:"Отчество,attr"` // Отчество
}

func (t *FullName) String() string {
	res := t.Surname

	if t.Name != "" {
		res += " " + t.Name
	}

	if t.Patronymic != "" {
		res += " " + t.Patronymic
	}

	return res
}

type Documenter interface {
	DebtamDocument | PaytaxDocument | RsmpDocument | SnrDocument | SshrDocument | TaxoffenceDocument
}

type TaxoffenceDocument struct {
	ID             uuid.UUID  `xml:"ИдДок,attr"`    // Идентификатор документа
	StateDate      *util.Date `xml:"ДатаСост,attr"` // Дата, по состоянию на которую, подготовлены данные для публикации
	GenerationDate *util.Date `xml:"ДатаДок,attr"`  // Дата формирования документа

	Taxpayer  *Legal     `xml:"СведНП"`    // Сведения о налогоплательщике, плательщике сбора, страхового взноса
	Penalties []*Penalty `xml:"СведНаруш"` // Сведения о наличии налоговых правонарушений и мерах ответственности за них с указанием общего размера штрафа
}

type Penalty struct {
	Sum float64 `xml:"СумШтраф,attr"` // Сумма штрафа
}

type SshrDocument struct {
	ID             uuid.UUID  `xml:"ИдДок,attr"`    // Идентификатор документа
	StateDate      *util.Date `xml:"ДатаСост,attr"` // Дата, по состоянию на которую, подготовлены данные для публикации
	GenerationDate *util.Date `xml:"ДатаДок,attr"`  // Дата формирования документа

	Taxpayer   *Legal       `xml:"СведНП"`   // Сведения о налогоплательщике, плательщике сбора, страхового взноса
	Headcounts []*Headcount `xml:"СведССЧР"` // Сведения о среднесписочной численности работников организации за календарный год
}

type Headcount struct {
	Count int `xml:"КолРаб,attr"` // Количество работников
}

type SnrDocument struct {
	ID             uuid.UUID  `xml:"ИдДок,attr"`    // Идентификатор документа
	StateDate      *util.Date `xml:"ДатаСост,attr"` // Дата, по состоянию на которую, подготовлены данные для публикации
	GenerationDate *util.Date `xml:"ДатаДок,attr"`  // Дата формирования документа

	Taxpayer *Legal          `xml:"СведНП"`  // Сведения о налогоплательщике, плательщике сбора, страхового взноса
	Regimes  []*FiscalRegime `xml:"СведСНР"` // Сведения о применении налогоплательщиком специальных налоговых режимов
}

type FiscalRegime struct {
	ESHN FnsBooleanStartsFrom0 `xml:"ПризнЕСХН,attr"` // Признак применения системы налогообложения в виде единого сельскохозяйственного налога
	USN  FnsBooleanStartsFrom0 `xml:"ПризнУСН,attr"`  // Признак применения упрощенной системы налогообложения
	AUSN FnsBooleanStartsFrom0 `xml:"ПризнАУСН,attr"` // Признак применения системы налогообложения в виде автоматизированной упрощенной системы налогообложения
	SRP  FnsBooleanStartsFrom0 `xml:"ПризнСРП,attr"`  // Признак применения системы налогообложения при выполнении соглашения о разделе продукции
}

type DebtamDocument struct {
	ID             uuid.UUID  `xml:"ИдДок,attr"`    // Идентификатор документа
	StateDate      *util.Date `xml:"ДатаСост,attr"` // Дата, по состоянию на которую, подготовлены данные для публикации
	GenerationDate *util.Date `xml:"ДатаДок,attr"`  // Дата формирования документа

	Taxpayer *Legal        `xml:"СведНП"`     // Сведения о налогоплательщике, плательщике сбора, страхового взноса
	Taxes    []*UnpayedTax `xml:"СведНедоим"` // Наименование налога (сбора, страховых взносов), денежного взыскания
}

type PaytaxDocument struct {
	ID             uuid.UUID  `xml:"ИдДок,attr"`    // Идентификатор документа
	StateDate      *util.Date `xml:"ДатаСост,attr"` // Дата, по состоянию на которую, подготовлены данные для публикации
	GenerationDate *util.Date `xml:"ДатаДок,attr"`  // Дата формирования документа

	Taxpayer *Legal      `xml:"СведНП"`      // Сведения о налогоплательщике, плательщике сбора, страхового взноса
	Taxes    []*PayedTax `xml:"СвУплСумНал"` // Сведения об уплаченных организацией в календарном году суммах налогов и сборов (по каждому налогу, сбору, страховому взносу) без учёта сумм налогов (сборов), уплаченных в связи с ввозом товаров на таможенную территорию Евразийского экономического союза, сумм налогов, уплаченных налоговым агентом
}

type UnpayedTax struct {
	Title     string   `xml:"НаимНалог,attr"`    // Наименование налога (сбора, страховых взносов), денежного взыскания
	Arrears   *float64 `xml:"СумНедНалог,attr"`  // Сумма недоимки по налогу
	Penalties *float64 `xml:"СумПени,attr"`      // Сумма пени
	Fines     *float64 `xml:"СумШтраф,attr"`     // Сумма штрафа
	Sum       *float64 `xml:"ОбщСумНедоим,attr"` // Общая сумма недоимки по налогу, пени и штрафу
}

type PayedTax struct {
	Title string   `xml:"НаимНалог,attr"` // Наименование налога (сбора, страховых взносов), денежного взыскания
	Sum   *float64 `xml:"СумУплНал,attr"` // Сумма уплаченного налога (сбора, страхового взноса)
}

type RsmpDocument struct {
	ID          uuid.UUID             `xml:"ИдДок,attr"`       // Идентификатор документа
	StateDate   *util.Date            `xml:"ДатаСост,attr"`    // По состоянию реестра на дату
	IncludeDate *util.Date            `xml:"ДатаВклМСП,attr"`  // Дата включения юридического лица / индивидуального предпринимателя в реестр МСП
	Type        DocumentTypeEnum      `xml:"ВидСубМСП,attr"`   // Вид субъекта МСП
	Category    DocumentCategoryEnum  `xml:"КатСубМСП,attr"`   // Категория субъекта МСП
	Renew       FnsBooleanStartsFrom1 `xml:"ПризНовМСП,attr"`  // Признак сведений о вновь созданном юридическом лице / вновь зарегистрированном индивидуальном предпринимателе
	Social      FnsBooleanStartsFrom1 `xml:"СведСоцПред,attr"` // Сведения о том, что юридическое лицо / индивидуальный предприниматель является социальным предприятием
	Employees   *int                  `xml:"ССЧР,attr"`        // Сведения о среднесписочной численности работников

	IndividualEntrepreneur *IndividualEntrepreneur `xml:"ИПВклМСП"`  // Сведения об индивидуальном предпринимателе, включенном в реестр МСП
	LegalEntity            *LegalEntity            `xml:"ОргВклМСП"` // Сведения о юридическом лице, включенном в реестр МСП

	Locations []*Location `xml:"СведМН"` // Сведения о месте нахождения юридического лица / месте жительства индивидуального предпринимателя

	Activities *DocumentActivity `xml:"СвОКВЭД"`  // Сведения о кодах по Общероссийскому классификатору видов экономической деятельности
	Licenses   []*License        `xml:"СвЛиценз"` // Сведения о лицензиях, выданных субъекту МСП
	Consumes   []*Consume        `xml:"СвПрод"`   // Сведения о производимой субъектом МСП продукции

	PartnerPrograms []*PartnerProgram `xml:"СвПрогПарт"` // Сведения о включении субъекта МСП в реестры программ партнерства
	Law44Contracts  []*Law44Contract  `xml:"СвКонтр"`    // Сведения о наличии у субъекта МСП в предшествующем календарном году контрактов, заключенных в соответствии с Федеральным законом от 5 апреля 2013 года №44-ФЗ
	Law223Contracts []*Law223Contract `xml:"СвДог"`      // Сведения о наличии у субъекта МСП в предшествующем календарном году договоров, заключенных в соответствии с Федеральным законом от 18 июля 2011 года №223-ФЗ
}

func (t *RsmpDocument) GetType() string {
	switch t.Type {
	case DocumentTypeLegalEntity:
		return "legal_entity"
	case DocumentTypeIndividualEntrepreneur:
		return "individual_entrepreneur"
	case DocumentTypePeasantFarmHead:
		return "peasant_farm_head"
	default:
		return ""
	}
}

func (t *RsmpDocument) GetCategory() string {
	switch t.Category {
	case DocumentCategoryEnumMicro:
		return "micro"
	case DocumentCategoryEnumSmall:
		return "small"
	case DocumentCategoryEnumMedium:
		return "medium"
	default:
		return ""
	}
}

func (t *RsmpDocument) Subject() Subjecter {
	if t.IndividualEntrepreneur != nil {
		return t.IndividualEntrepreneur
	}

	return t.LegalEntity
}

type DocumentTypeEnum string

const (
	DocumentTypeLegalEntity            DocumentTypeEnum = "1" // юридическое лицо
	DocumentTypeIndividualEntrepreneur DocumentTypeEnum = "2" // индивидуальный предприниматель
	DocumentTypePeasantFarmHead        DocumentTypeEnum = "3" // глава крестьянско-фермерского хозяйства
)

type DocumentCategoryEnum string

const (
	DocumentCategoryEnumMicro  DocumentCategoryEnum = "1" // микропредприятие
	DocumentCategoryEnumSmall  DocumentCategoryEnum = "2" // малое предприятие
	DocumentCategoryEnumMedium DocumentCategoryEnum = "3" // среднее предприятие
)

type FnsBooleanStartsFrom0 string

const (
	FnsBooleanStartsFrom0Yes FnsBooleanStartsFrom0 = "1" // да
	FnsBooleanStartsFrom0No  FnsBooleanStartsFrom0 = "0" // нет
)

func (t *FnsBooleanStartsFrom0) Bool() *bool {
	var res bool
	switch *t {
	case FnsBooleanStartsFrom0Yes:
		res = true
	case FnsBooleanStartsFrom0No:
		res = false
	default:
		return nil
	}
	return &res
}

type FnsBooleanStartsFrom1 string

const (
	FnsBooleanStartsFrom1Yes FnsBooleanStartsFrom1 = "1" // да
	FnsBooleanStartsFrom1No  FnsBooleanStartsFrom1 = "2" // нет
)

type Subjecter interface {
	GetName() string
	GetShortName() string
	GetINN() string
	GetOGRN() string
}

type IndividualEntrepreneur struct {
	FullName *FullName `xml:"ФИОИП"` // Фамилия, имя, отчество индивидуального предпринимателя

	INN    string `xml:"ИННФЛ,attr"`  // ИНН индивидуального предпринимателя
	OGRNIP string `xml:"ОГРНИП,attr"` // ОГРНИП индивидуального предпринимателя (главы крестьянско-фермерского хозяйства)
}

func (t *IndividualEntrepreneur) GetName() string {
	return t.FullName.String()
}

func (t *IndividualEntrepreneur) GetShortName() string {
	return ""
}

func (t *IndividualEntrepreneur) GetINN() string {
	return t.INN
}

func (t *IndividualEntrepreneur) GetOGRN() string {
	return t.OGRNIP
}

type Legal struct {
	Name string `xml:"НаимОрг,attr"` // Полное наименование юридического лица на русском языке
	INN  string `xml:"ИННЮЛ,attr"`   // ИНН юридического лица
}

type LegalEntity struct {
	*Legal
	ShortName string `xml:"НаимОргСокр,attr"` // Сокращенное наименование юридического лица на русском языке
	OGRN      string `xml:"ОГРН,attr"`        // ОГРН юридического лица
}

func (t *LegalEntity) GetName() string {
	return t.Name
}

func (t *LegalEntity) GetShortName() string {
	return t.ShortName
}

func (t *LegalEntity) GetINN() string {
	return t.INN
}

func (t *LegalEntity) GetOGRN() string {
	return t.OGRN
}

type Location struct {
	RegionCode string `xml:"КодРегион,attr"` // Код Региона

	Region     *LocationAddress `xml:"Регион"`     // Субъект Российской Федерации
	District   *LocationAddress `xml:"Район"`      // Район (улус и т.п.)
	City       *LocationAddress `xml:"Город"`      // Город (волость и т.п.)
	Settlement *LocationAddress `xml:"НаселПункт"` // Населенный пункт (село и т.п.)
}

type LocationAddress struct {
	Type  string `xml:"Тип,attr"`  // Тип адресного объекта
	Title string `xml:"Наим,attr"` // Наименование
}

type DocumentActivity struct {
	Primary   *Activity   `xml:"СвОКВЭДОсн"` // Сведения об основном виде деятельности
	Secondary []*Activity `xml:"СвОКВЭДДоп"` // Сведения о дополнительных видах деятельности
}

type Activity struct {
	Code    string `xml:"КодОКВЭД,attr"`  // Код вида деятельности по Общероссийскому классификатору видов экономической деятельности
	Title   string `xml:"НаимОКВЭД,attr"` // Наименование вида деятельности по Общероссийскому классификатору видов экономической деятельности
	Version string `xml:"ВерсОКВЭД,attr"` // Признак версии Общероссийского классификатора видов экономической деятельности
}

type License struct {
	Title string `xml:"НаимЛицВД"` //Наименование лицензируемого вида деятельности, на который выдана лицензия

	Series    string     `xml:"СерЛиценз,attr"`     // Серия лицензии
	Number    string     `xml:"НомЛиценз,attr"`     // Номер лицензии
	Type      string     `xml:"ВидЛиценз,attr"`     // Вид лицензии
	IssueDate *util.Date `xml:"ДатаЛиценз,attr"`    // Дата лицензии
	Issuer    string     `xml:"ОргВыдЛиценз,attr"`  // Наименование лицензирующего органа, выдавшего или переоформившего лицензию
	StartDate *util.Date `xml:"ДатаНачЛиценз,attr"` // Дата начала действия лицензии
	EndDate   *util.Date `xml:"ДатаКонЛиценз,attr"` // Дата окончания действия лицензии
	StopDate  *util.Date `xml:"ДатаОстЛиценз,attr"` // Дата приостановления действия лицензии
	Stopper   string     `xml:"ОргОстЛиценз,attr"`  // Наименование лицензирующего органа, приостановившего действие лицензии
}

type Consume struct {
	Code       string                `xml:"КодПрод,attr"`   // Код вида продукции
	Title      string                `xml:"НаимПрод,attr"`  // Наименование вида продукции
	Innovation FnsBooleanStartsFrom1 `xml:"ПрОтнПрод,attr"` // Признак отнесения продукции к инновационной, высокотехнологичной
}

type PartnerProgram struct {
	CustomerTitle  string     `xml:"НаимЮЛ_ПП,attr"` // Наименование заказчика, реализующего программу партнерства
	CustomerINN    string     `xml:"ИННЮЛ_ПП,attr"`  // ИНН заказчика, реализующего программу партнерства
	ContractNumber string     `xml:"НомДог,attr"`    // Номер договора о присоединении к выбранной программе партнерства
	ContractDate   *util.Date `xml:"ДатаДог,attr"`   // Дата договора о присоединении к выбранной программе партнерства
}

type Law44Contract struct {
	CustomerTitle  string     `xml:"НаимЮЛ_ЗК,attr"`      // Наименование заказчика по контракту
	CustomerINN    string     `xml:"ИННЮЛ_ЗК,attr"`       // ИНН заказчика по контракту
	Title          string     `xml:"ПредмКонтр,attr"`     // Предмет контракта
	ContractNumber string     `xml:"НомКонтрРеестр,attr"` // Реестровый номер контракта
	ContractDate   *util.Date `xml:"ДатаКонтр,attr"`      // Дата заключения контракта
}

type Law223Contract struct {
	CustomerTitle  string     `xml:"НаимЮЛ_ЗД,attr"`    // Наименование заказчика по договору
	CustomerINN    string     `xml:"ИННЮЛ_ЗД,attr"`     // ИНН заказчика по договору
	Title          string     `xml:"ПредмДог,attr"`     // Предмет договора
	ContractNumber string     `xml:"НомДогРеестр,attr"` // Реестровый номер договора
	ContractDate   *util.Date `xml:"ДатаДог,attr"`      // Дата заключения договора
}
