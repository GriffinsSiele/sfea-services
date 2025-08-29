from worker_classes.keydb.interfaces import FieldDescription, FieldXMLDescriptor

field_XML_description: FieldXMLDescriptor = {
    "access_level_age": FieldDescription(
        description="Видимость возраста в профиле",
        title="Видимость возраста",
        type="string",
    ),
    "access_level_feed": FieldDescription(
        description="Видимость новостей в профиле",
        title="Видимость новостей в профиле",
        type="string",
    ),
    "access_level_now": FieldDescription(
        description='Видимость "в сети" в профиле',
        title='Видимость "в сети" в профиле',
        type="string",
    ),
    "access_level_video": FieldDescription(
        description="Видимость видео в профиле",
        title="Видимость видео в профиле",
        type="string",
    ),
    "accessible": FieldDescription(
        description="Доступность профиля",
        title="Доступность профиля",
        type="string",
    ),
    "age": FieldDescription(
        description="Возраст",
        title="Возраст",
        type="integer",
    ),
    "allows_anonym_access": FieldDescription(
        description="Видимость профиля неавторизованному пользователю",
        title="Видимость профиля неавторизованному пользователю",
        type="string",
    ),
    "allows_messaging_only_for_friends": FieldDescription(
        description="Возможность отправки сообщений только друзьям",
        title="Отправка сообщений только друзьям",
        type="string",
    ),
    "bio": FieldDescription(
        description="Информация о себе",
        title="О себе",
        type="text",
    ),
    "birthday": FieldDescription(
        description="Дата рождения",
        title="Дата рождения",
        type="string",
    ),
    "birthday_set": FieldDescription(
        description="Установлена дата рождения в профиле",
        title="Установлена дата рождения в профиле",
        type="string",
    ),
    "counter_applications": FieldDescription(
        description="Количество приложений ОК",
        title="Количество приложений ОК",
        type="integer",
    ),
    "counter_friends": FieldDescription(
        description="Количество друзей",
        title="Количество друзей",
        type="integer",
    ),
    "counter_groups": FieldDescription(
        description="Количество групп",
        title="Количество групп",
        type="integer",
    ),
    "counter_photo_albums": FieldDescription(
        description="Количество фотоальбомов",
        title="Количество фотоальбомов",
        type="integer",
    ),
    "counter_photos_personal": FieldDescription(
        description="Количество персональных фото",
        title="Количество персональных фото",
        type="integer",
    ),
    "counter_products": FieldDescription(
        description="Количество товаров",
        title="Количество товаров",
        type="integer",
    ),
    "counter_subscribers": FieldDescription(
        description="Количество подписчиков",
        title="Количество подписчиков",
        type="integer",
    ),
    "first_name": FieldDescription(description="Имя", title="Имя", type="string"),
    "gender": FieldDescription(description="Пол", title="Пол", type="string"),
    "invited_by_friend": FieldDescription(
        description="Пользователь приглашен другом в ОК",
        title="Пользователь приглашен другом в ОК",
        type="string",
    ),
    "is_merchant": FieldDescription(
        description="Профиль для бизнеса",
        title="Профиль для бизнеса",
        type="string",
    ),
    "is_new_user": FieldDescription(
        description="Новый профиль",
        title="Новый профиль",
        type="string",
    ),
    "last_name": FieldDescription(
        description="Фамилия", title="Фамилия", type="string", field="lastname"
    ),
    "shortname": FieldDescription(
        description="Ник", title="Ник", type="nick", field="nick"
    ),
    "last_online": FieldDescription(
        description='Последний раз "в сети"',
        title='Последний раз "в сети"',
        type="datetime",
    ),
    "relationship": FieldDescription(
        description="Положение", title="Положение", type="string", field="relationship"
    ),
    "locale": FieldDescription(
        description="Язык",
        title="Язык",
        type="string",
    ),
    "location_city": FieldDescription(
        description="Город",
        title="Город",
        type="string",
    ),
    "location_country": FieldDescription(
        description="Страна",
        title="Страна",
        type="string",
    ),
    "location_of_birth_city": FieldDescription(
        description="Город рождения",
        title="Город рождения",
        type="string",
    ),
    "location_of_birth_country": FieldDescription(
        description="Страна рождения",
        title="Страна рождения",
        type="string",
    ),
    "name": FieldDescription(
        description="Полное имя профиля",
        title="Полное имя профиля",
        type="string",
    ),
    "photos": FieldDescription(
        description="Фотография",
        title="Фотография",
        type="image",
    ),
    "avatar": FieldDescription(
        description="Аватар",
        title="Аватар",
        type="image",
    ),
    "premium": FieldDescription(
        description="Премиум",
        title="Премиум",
        type="string",
    ),
    "private": FieldDescription(
        description="Приватный",
        title="Приватный",
        type="string",
    ),
    "blocked": FieldDescription(
        description="Заблокирован",
        title="Заблокирован",
        type="string",
    ),
    "relationships": FieldDescription(
        description="Отношение",
        title="Отношение",
        type="string",
    ),
    "registered_date": FieldDescription(
        description="Дата регистрации",
        title="Дата регистрации",
        type="datetime",
    ),
    "url_profile": FieldDescription(
        description="Ссылка профиля",
        title="Ссылка профиля",
        type="url",
    ),
    "communities_type": FieldDescription(
        description="Карьера",
        title="Карьера",
        type="string",
    ),
    "communities_name": FieldDescription(
        description="Название",
        title="Название",
        type="string",
    ),
    "communities_abbreviation": FieldDescription(
        description="Аббревиатура",
        title="Аббревиатура",
        type="string",
    ),
    "communities_city": FieldDescription(
        description="Город",
        title="Город",
        type="string",
    ),
    "communities_country": FieldDescription(
        description="Страна",
        title="Страна",
        type="string",
    ),
    "communities_address": FieldDescription(
        description="Адрес",
        title="Адрес",
        type="address",
    ),
    "communities_year_from": FieldDescription(
        description="Год начала",
        title="Год начала",
        type="string",
    ),
    "communities_year_to": FieldDescription(
        description="Год окончания",
        title="Год окончания",
        type="string",
    ),
    "groups_name": FieldDescription(
        description="Название группы",
        title="Название группы",
        type="string",
    ),
    "groups_description": FieldDescription(
        description="Описание группы",
        title="Описание группы",
        type="text",
    ),
    "groups_created": FieldDescription(
        description="Дата создания",
        title="Дата создания",
        type="datetime",
    ),
    "groups_avatar": FieldDescription(description="Аватар", title="Аватар", type="image"),
    "groups_premium": FieldDescription(
        description="Премиум",
        title="Премиум",
        type="string",
    ),
    "groups_private": FieldDescription(
        description="Приватный",
        title="Приватный",
        type="string",
    ),
    "groups_category": FieldDescription(
        description="Категория",
        title="Категория",
        type="string",
    ),
    "groups_members": FieldDescription(
        description="Количество участников",
        title="Количество участников",
        type="integer",
    ),
    "Type": FieldDescription(
        description="Тип записи",
        title="Тип записи",
        type="string",
    ),
}
