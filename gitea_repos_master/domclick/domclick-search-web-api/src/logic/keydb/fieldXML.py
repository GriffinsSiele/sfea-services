from worker_classes.keydb.interfaces import FieldDescription, FieldXMLDescriptor

field_XML_description: FieldXMLDescriptor = {
    "first_name": FieldDescription(
        title="Имя",
        type="string",
    ),
    "middle_name": FieldDescription(
        title="Отчество",
        type="string",
    ),
    "last_name": FieldDescription(
        title="Инициал фамилии",
        type="string",
    ),
    "user_id": FieldDescription(
        title="Идентификатор пользователя",
        type="integer",
    ),
    "is_registered": FieldDescription(
        title="Пользователь зарегистрирован в системе",
        type="string",
    ),
    "is_partner": FieldDescription(
        title="Пользователь является партнером системы",
        type="string",
    ),
    "avatar": FieldDescription(
        title="Аватар",
        type="image",
    ),
    "client_review": FieldDescription(
        title="Рейтинг пользователя",
        type="float",
    ),
    "registered_at": FieldDescription(
        title="Дата регистрации",
        type="datetime",
    ),
    "deals_count": FieldDescription(
        title="Количество сделок",
        type="integer",
    ),
    "client_comments_count": FieldDescription(
        title="Количество комментариев",
        type="integer",
    ),
    "partner_link": FieldDescription(
        title="Ссылка на профиль",
        type="url",
    ),
}
