from worker_classes.keydb.interfaces import FieldDescription, FieldXMLDescriptor

field_XML_description: FieldXMLDescriptor = {
    "Name": FieldDescription(
        title="Имя",
        type="string",
    ),
    "alternative_name": FieldDescription(
        title="Альтернативное имя",
        type="string",
    ),
    "phone_number_type": FieldDescription(
        title="Тип телефона",
        type="string",
    ),
    "country_code": FieldDescription(
        title="Код страны",
        type="string",
    ),
    "phone_number_operator": FieldDescription(
        title="Оператор",
        type="string",
    ),
    "extra_phone": FieldDescription(
        title="Доп. номера",
        type="phone",
    ),
    "badges": FieldDescription(
        title="Тип записи",
        type="string",
    ),
    "score": FieldDescription(
        title="Рейтинг",
        type="float",
    ),
    "gender": FieldDescription(
        title="Пол",
        type="string",
    ),
    "about": FieldDescription(
        title="О себе",
        type="text",
    ),
    "job_title": FieldDescription(
        title="Должность",
        type="string",
    ),
    "company": FieldDescription(
        title="Компания",
        type="string",
    ),
    "search_warnings": FieldDescription(
        title="Возможная категория",
        type="string",
    ),
    "address": FieldDescription(
        title="Адрес",
        type="address",
    ),
    "spam_score": FieldDescription(
        title="Спам-рейтинг",
        type="integer",
    ),
    "spam_category": FieldDescription(
        title="Тип спама",
        type="string",
    ),
    "comments_count": FieldDescription(
        title="Количество комментариев",
        type="integer",
    ),
    "comments_exists": FieldDescription(
        title="Возможность написания комментариев под профилем",
        type="string",
    ),
    "tags": FieldDescription(
        title="Теги",
        type="text",
    ),
    "is_install_app": FieldDescription(
        title="Пользователь зарегистрирован в Truecaller",
        type="string",
    ),
    "birthday": FieldDescription(
        title="Дата рождения",
        type="string",
    ),
    "avatar": FieldDescription(
        title="Аватар",
        type="image",
    ),
    "email": FieldDescription(
        title="Почта",
        type="email",
    ),
    "link": FieldDescription(
        title="Ссылка",
        type="url",
    ),
    "facebook": FieldDescription(
        title="Facebook",
        type="url",
    ),
    "twitter": FieldDescription(
        title="Twitter",
        type="url",
    ),
}
