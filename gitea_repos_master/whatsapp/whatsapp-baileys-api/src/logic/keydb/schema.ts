export const fieldXML: {
  [k: string]: {
    field: string;
    description?: string;
    title: string;
    type: string;
  };
} = {
  ResultCode: {
    field: 'ResultCode',
    title: 'Код результата',
    type: 'string'
  },
  phone: {
    field: 'phone',
    title: 'Телефон',
    type: 'string'
  },
  status: {
    field: 'StatusText',
    title: 'Статус',
    type: 'string'
  },
  statusSetAt: {
    field: 'StatusDate',
    title: 'Время установки статуса',
    type: 'string'
  },
  statusHidden: {
    field: 'StatusHidden',
    description: 'Статус скрыт в настройках конфиденциальности',
    title: 'Статус скрыт',
    type: 'string'
  },
  isBusiness: {
    field: 'BusinessAccount',
    title: 'Бизнес-аккаунт',
    type: 'string'
  },
  businessAddress: {
    field: 'BusinessAddress',
    description: 'Адрес, указанный в бизнес-профиле',
    title: 'Адрес',
    type: 'string'
  },
  businessDescription: {
    field: 'BusinessDescription',
    description: 'Описание, указанное в бизнес-профиле',
    title: 'Описание',
    type: 'text'
  },
  businessCategory: {
    field: 'BusinessCategory',
    description: 'Категория, указанная в бизнес-профиле',
    title: 'Категория',
    type: 'string'
  },
  businessEmail: {
    field: 'BusinessEmail',
    description: 'Почта, указанная в бизнес-профиле',
    title: 'Почта',
    type: 'email'
  },
  businessWebsite: {
    field: 'BusinessWebsite',
    description: 'Сайт, указанный в бизнес-профиле',
    title: 'Сайт',
    type: 'url'
  },
  businessTimezone: {
    field: 'BusinessTimezone',
    description: 'Часовой пояс, указанный в бизнес-профиле',
    title: 'Часовой пояс',
    type: 'string'
  },
  businessSchedule: {
    field: 'BusinessSchedule',
    description: 'График работы, указанный в бизнес-профиле',
    title: 'График работы',
    type: 'text'
  },
  previewURL: {
    field: 'PhotoURL',
    description: 'Ссылка на превью аватара',
    title: 'Аватар',
    type: 'image'
  },
  previewBase64: {
    field: 'Photo',
    title: 'Аватар',
    type: 'image'
  },
  imageURL: {
    field: 'FullPhoto',
    title: 'Фото',
    type: 'image'
  },
  avatarHidden: {
    field: 'AvatarHidden',
    description: 'Аватар скрыт в настройках конфиденциальности',
    title: 'Аватар скрыт',
    type: 'string'
  },
  hasAvatar: {
    field: 'HasAvatar',
    description: 'Установлен аватар',
    title: 'Установлен аватар',
    type: 'string'
  }
};
