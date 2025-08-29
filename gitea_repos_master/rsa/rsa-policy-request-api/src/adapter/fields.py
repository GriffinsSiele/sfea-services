import re
from enum import Enum


class FieldXML(Enum):
    PolicyNumber = 'PolicyNumber'
    Company = 'Company'
    PolicyStatus = 'PolicyStatus'
    Purpose = 'Purpose'
    Limited = 'Limited'
    Insurant = 'Insurant'
    InsurantBirthDate = 'InsurantBirthDate'
    Owner = 'Owner'
    OwnerBirthDate = 'OwnerBirthDate'
    Kbm = 'Kbm'
    Region = 'Region'
    Total = 'Total'
    Model = 'Model'
    Category = 'Category'
    VIN = 'VIN'
    BodyNum = 'BodyNum'
    Power = 'Power'


FieldXMLDescription = {
    FieldXML.PolicyNumber: {
        'field': FieldXML.PolicyNumber.value,
        'description': 'Полис ОСАГО',
        'title': 'Полис ОСАГО',
        'type': 'string'
    },
    FieldXML.Company: {
        'field': FieldXML.Company.value,
        'description': 'Страховая компания',
        'title': 'Страховая компания',
        'type': 'string'
    },
    FieldXML.PolicyStatus: {
        'field': FieldXML.PolicyStatus.value,
        'description': 'Статус полиса',
        'title': 'Статус полиса',
        'type': 'string'
    },
    FieldXML.Purpose: {
        'field': FieldXML.Purpose.value,
        'description': 'Цель использования',
        'title': 'Цель использования',
        'type': 'string'
    },
    FieldXML.Limited: {
        'field': FieldXML.Limited.value,
        'description': 'Ограничения',
        'title': 'Ограничения',
        'type': 'string'
    },
    FieldXML.Insurant: {
        'field': FieldXML.Insurant.value,
        'description': 'Cтрахователь',
        'title': 'Cтрахователь',
        'type': 'string'
    },
    FieldXML.InsurantBirthDate: {
        'field': FieldXML.InsurantBirthDate.value,
        'description': 'Дата рождения страхователя',
        'title': 'Дата рождения страхователя',
        'type': 'string'
    },
    FieldXML.Owner: {
        'field': FieldXML.Owner.value,
        'description': 'Cобственник',
        'title': 'Cобственник',
        'type': 'string'
    },
    FieldXML.OwnerBirthDate: {
        'field': FieldXML.OwnerBirthDate.value,
        'description': 'Дата рождения собственникаО',
        'title': 'Дата рождения собственника',
        'type': 'string'
    },
    FieldXML.Kbm: {
        'field': FieldXML.Kbm.value,
        'description': 'КБМ',
        'title': 'КБМ',
        'type': 'string'
    },
    FieldXML.Region: {
        'field': FieldXML.Region.value,
        'description': 'Регион',
        'title': 'Регион',
        'type': 'string'
    },
    FieldXML.Total: {
        'field': FieldXML.Total.value,
        'description': 'Страховая премия',
        'title': 'Страховая премия',
        'type': 'string'
    },
    FieldXML.Model: {
        'field': FieldXML.Model.value,
        'description': 'Марка и модель',
        'title': 'Марка и модель',
        'type': 'string'
    },
    FieldXML.Category: {
        'field': FieldXML.Category.value,
        'description': 'Категория',
        'title': 'Категория',
        'type': 'string'
    },
    FieldXML.VIN: {
        'field': FieldXML.VIN.value,
        'description': 'VIN',
        'title': 'VIN',
        'type': 'string'
    },
    FieldXML.BodyNum: {
        'field': FieldXML.BodyNum.value,
        'description': 'Номер кузова',
        'title': 'Номер кузова',
        'type': 'string'
    },
    FieldXML.Power: {
        'field': FieldXML.Power.value,
        'description': 'Мощность двигателя, л.с.',
        'title': 'Мощность двигателя, л.с.',
        'type': 'string'
    }
}

HTMLtoXMLFields = {
    FieldXML.PolicyNumber: 'Серия и номер договора ОСАГО',
    FieldXML.Company: 'Наименование страховой организации',
    FieldXML.PolicyStatus: 'Статус договора ОСАГО',
    FieldXML.Purpose: 'Цель использования транспортного средства',
    FieldXML.Limited: 'Договор ОСАГО с ограничениями/без ограничений лиц, допущенных к управлению',
    FieldXML.Insurant: 'Сведения о страхователе транспортного средства',
    FieldXML.InsurantBirthDate: 'Сведения о страхователе транспортного средства',
    FieldXML.Owner: 'Сведения о собственнике транспортного средства',
    FieldXML.OwnerBirthDate: 'Сведения о собственнике транспортного средства',
    FieldXML.Kbm: 'КБМ по договору ОСАГО',
    FieldXML.Region: 'Транспортное средство используется в регионе',
    FieldXML.Total: 'Страховая премия',
    FieldXML.Model: 'Марка и модель транспортного',
    FieldXML.Category: 'Марка и модель транспортного',
    FieldXML.VIN: 'VIN',
    FieldXML.BodyNum: 'Номер кузова',
    FieldXML.Power: 'Мощность двигателя для категории'
}

remove_date_birth = lambda v: re.sub('( [\d\.]{10})', '', v)
remove_FIO = lambda v: re.match('([\d\.]{10})', v)

fieldConverter = {
    FieldXML.Insurant: remove_date_birth,
    FieldXML.InsurantBirthDate: remove_FIO,
    FieldXML.Owner: remove_date_birth,
    FieldXML.OwnerBirthDate: remove_FIO,
    FieldXML.Model: lambda v: re.sub('( \(.*\))', '', v),
    FieldXML.Category: lambda v: re.match('«(.)»', v),
}


def buildXML(field, value):
    return {**FieldXMLDescription[field], 'value': value}