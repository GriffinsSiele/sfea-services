from worker_classes.keydb.interfaces import FieldDescription, FieldXMLDescriptor

field_XML_description: FieldXMLDescriptor = {
    "name": FieldDescription(
        title="Имя",
        type="string",
    ),
    "description": FieldDescription(
        title="Описание",
        type="text",
    ),
    "user_definition": FieldDescription(
        title="Статус пользователя",
        type="text",
    ),
    "avatar": FieldDescription(
        title="Аватар",
        type="image",
    ),
    "is_own_photo": FieldDescription(
        title="Собственный аватар",
        type="string",
    ),
    "birthday": FieldDescription(
        title="Дата рождения",
        type="string",
    ),
    "email": FieldDescription(
        title="Почта",
        type="email",
    ),
    "facebook": FieldDescription(
        title="Страница Facebook",
        type="url",
    ),
    "twitter": FieldDescription(
        title="Страница Twitter",
        type="url",
    ),
    "linkedin": FieldDescription(
        title="Страница LinkedIn",
        type="url",
    ),
    "foursquare": FieldDescription(
        title="Страница Foursquare",
        type="url",
    ),
    "vkontakte": FieldDescription(
        title="Страница VK",
        type="url",
    ),
    "instagram_id": FieldDescription(
        title="Идентификатор пользователя в Instagram",
        type="string",
    ),
    "pinterest": FieldDescription(
        title="Pinterest",
        type="url",
    ),
    "website": FieldDescription(
        title="Сайт",
        type="url",
    ),
    "address": FieldDescription(
        title="Адрес",
        type="string",
    ),
    "google_maps": FieldDescription(
        title="Ссылка на Google Maps",
        type="url",
    ),
    "coordinates": FieldDescription(
        title="Местоположение",
        type="map",
    ),
    "schedule": FieldDescription(
        title="Расписание работы",
        type="string",
    ),
    "categories": FieldDescription(
        title="Категории",
        type="string",
    ),
    "reviews": FieldDescription(
        title="Отзывы",
        type="text",
    ),
    "rating": FieldDescription(
        title="Рейтинг",
        type="float",
    ),
    "is_spam": FieldDescription(
        title="Является спам-номером",
        type="string",
    ),
    "price_level": FieldDescription(
        title="Уровень цен",
        description="Оценка Google Карт уровня цен",
        type="string",
    ),
    "is_install_app": FieldDescription(
        title="Зарегистрирован в CallApp",
        type="string",
    ),
    "active_during_period": FieldDescription(
        title="Использует CallApp",
        type="string",
    ),
    "priority": FieldDescription(
        title="Приоритет",
        type="integer",
    ),
}
