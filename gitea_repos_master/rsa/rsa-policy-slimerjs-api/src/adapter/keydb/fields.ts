export function buildXML(field: string, value: any) {
  return { ...fieldXMLDescription[field], value };
}

export const fieldXML = {
  PolicyNumber: 'PolicyNumber',
  Company: 'Company',
  PolicyStatus: 'PolicyStatus',
  Purpose: 'Purpose',
  Limited: 'Limited',
  Insurant: 'Insurant',
  InsurantBirthDate: 'InsurantBirthDate',
  Owner: 'Owner',
  OwnerBirthDate: 'OwnerBirthDate',
  Kbm: 'Kbm',
  Region: 'Region',
  Total: 'Total',
  Model: 'Model',
  Category: 'Category',
  VIN: 'VIN',
  BodyNum: 'BodyNum',
  Power: 'Power'
};

export const fieldXMLDescription: {
  [k: string]: {
    field: string;
    description: string;
    title: string;
    type: string;
  };
} = {
  [fieldXML.PolicyNumber]: {
    field: fieldXML.PolicyNumber,
    description: 'Полис ОСАГО',
    title: 'Полис ОСАГО',
    type: 'string'
  },
  [fieldXML.Company]: {
    field: fieldXML.Company,
    description: 'Страховая компания',
    title: 'Страховая компания',
    type: 'string'
  },
  [fieldXML.PolicyStatus]: {
    field: fieldXML.PolicyStatus,
    description: 'Статус полиса',
    title: 'Статус полиса',
    type: 'string'
  },
  [fieldXML.Purpose]: {
    field: fieldXML.Purpose,
    description: 'Цель использования',
    title: 'Цель использования',
    type: 'string'
  },
  [fieldXML.Limited]: {
    field: fieldXML.Limited,
    description: 'Ограничения',
    title: 'Ограничения',
    type: 'string'
  },
  [fieldXML.Insurant]: {
    field: fieldXML.Insurant,
    description: 'Cтрахователь',
    title: 'Cтрахователь',
    type: 'string'
  },
  [fieldXML.InsurantBirthDate]: {
    field: fieldXML.InsurantBirthDate,
    description: 'Дата рождения страхователя',
    title: 'Дата рождения страхователя',
    type: 'string'
  },
  [fieldXML.Owner]: {
    field: fieldXML.Owner,
    description: 'Cобственник',
    title: 'Cобственник',
    type: 'string'
  },
  [fieldXML.OwnerBirthDate]: {
    field: fieldXML.OwnerBirthDate,
    description: 'Дата рождения собственникаО',
    title: 'Дата рождения собственника',
    type: 'string'
  },
  [fieldXML.Kbm]: {
    field: fieldXML.Kbm,
    description: 'КБМ',
    title: 'КБМ',
    type: 'string'
  },
  [fieldXML.Region]: {
    field: fieldXML.Region,
    description: 'Регион',
    title: 'Регион',
    type: 'string'
  },
  [fieldXML.Total]: {
    field: fieldXML.Total,
    description: 'Страховая премия',
    title: 'Страховая премия',
    type: 'string'
  },
  [fieldXML.Model]: {
    field: fieldXML.Model,
    description: 'Марка и модель',
    title: 'Марка и модель',
    type: 'string'
  },
  [fieldXML.Category]: {
    field: fieldXML.Category,
    description: 'Категория',
    title: 'Категория',
    type: 'string'
  },
  [fieldXML.VIN]: {
    field: fieldXML.VIN,
    description: 'VIN',
    title: 'VIN',
    type: 'string'
  },
  [fieldXML.BodyNum]: {
    field: fieldXML.BodyNum,
    description: 'Номер кузова',
    title: 'Номер кузова',
    type: 'string'
  },
  [fieldXML.Power]: {
    field: fieldXML.Power,
    description: 'Мощность двигателя, л.с.',
    title: 'Мощность двигателя, л.с.',
    type: 'string'
  }
};

export const HTMLtoXMLFields = {
  [fieldXML.PolicyNumber]: 'Серия и номер договора ОСАГО',
  [fieldXML.Company]: 'Наименование страховой организации',
  [fieldXML.PolicyStatus]: 'Статус договора ОСАГО',
  [fieldXML.Purpose]: 'Цель использования транспортного средства',
  [fieldXML.Limited]:
    'Договор ОСАГО с ограничениями/без ограничений лиц, допущенных к управлению',
  [fieldXML.Insurant]: 'Сведения о страхователе транспортного средства',
  [fieldXML.InsurantBirthDate]:
    'Сведения о страхователе транспортного средства',
  [fieldXML.Owner]: 'Сведения о собственнике транспортного средства',
  [fieldXML.OwnerBirthDate]: 'Сведения о собственнике транспортного средства',
  [fieldXML.Kbm]: 'КБМ по договору ОСАГО',
  [fieldXML.Region]: 'Транспортное средство используется в регионе',
  [fieldXML.Total]: 'Страховая премия',
  [fieldXML.Model]: 'Марка и модель транспортного',
  [fieldXML.Category]: 'Марка и модель транспортного',
  [fieldXML.VIN]: 'VIN',
  [fieldXML.BodyNum]: 'Номер кузова',
  [fieldXML.Power]: 'Мощность двигателя для категории'
};

const removeDateBirth = (v: string) => v.replace(/( [\d\.]{10})/g, '');
const removeFIO = (v: string) => v.match(/([\d\.]{10})/g)?.[0] ?? '';

export const fieldConverter = {
  [fieldXML.Insurant]: removeDateBirth,
  [fieldXML.InsurantBirthDate]: removeFIO,
  [fieldXML.Owner]: removeDateBirth,
  [fieldXML.OwnerBirthDate]: removeFIO,
  [fieldXML.Model]: (v: string) => v.replace(/( \(.*\))/g, ''),
  [fieldXML.Category]: (v: string) =>
    (v.match(/«(.)»/g)?.[0] ?? '').replace('/«|»/g,\'\'', '')
};
