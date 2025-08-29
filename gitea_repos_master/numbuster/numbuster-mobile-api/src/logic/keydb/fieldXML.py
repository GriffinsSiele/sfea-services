from worker_classes.keydb.interfaces import FieldDescription, FieldXMLDescriptor

field_XML_description: FieldXMLDescriptor = {
    "avatar": FieldDescription(
        title="Аватар",
        type="image",
    ),
    "bans": FieldDescription(
        title="Количество блокировок",
        type="integer",
    ),
    "caller_type": FieldDescription(
        title="Тип записи",
        type="string",
    ),
    "comments_count": FieldDescription(
        title="Количество комментариев",
        type="integer",
    ),
    "names_count": FieldDescription(
        title="Количество возможных имен",
        type="integer",
    ),
    "emotags": FieldDescription(
        title="Реакции пользователей",
        type="text",
    ),
    "first_name": FieldDescription(
        title="Имя",
        type="string",
    ),
    "name": FieldDescription(
        title="Полное имя",
        type="string",
    ),
    "index": FieldDescription(
        title="Индекс",
        type="float",
    ),
    "instagram": FieldDescription(
        title="Instagram",
        type="string",
    ),
    "is_banned": FieldDescription(
        title="Забанен",
        type="string",
    ),
    "is_install_app": FieldDescription(
        title="Установлено приложение Numbuster",
        type="string",
    ),
    "bio": FieldDescription(
        title="О себе",
        type="string",
    ),
    "is_hidden": FieldDescription(title="Скрыт", type="string", field="IsHidden"),
    "is_pro": FieldDescription(
        title="Имеет подписку Pro",
        type="string",
    ),
    "is_unwanted": FieldDescription(
        title="Нежелательный",
        type="string",
        field="IsUnwanted",
    ),
    "is_verified": FieldDescription(title="Проверен", type="string", field="IsVerified"),
    "last_name": FieldDescription(
        title="Фамилия",
        type="string",
    ),
    "operator": FieldDescription(
        title="Оператор",
        type="string",
    ),
    "region": FieldDescription(
        title="Регион",
        type="string",
    ),
    "register": FieldDescription(
        title="Дата регистрации в Numbuster",
        type="string",
    ),
    "tags": FieldDescription(
        title="Теги",
        type="text",
    ),
    "comment__author": FieldDescription(
        title="Автор",
        type="string",
    ),
    "comment__datetime": FieldDescription(
        title="Дата комментария",
        type="string",
    ),
    "comment__dislikes": FieldDescription(
        title="Количество дизлайков",
        type="integer",
    ),
    "comment__likes": FieldDescription(
        title="Количество лайков",
        type="integer",
    ),
    "comment__text": FieldDescription(
        title="Комментарий",
        type="text",
    ),
    "likes": FieldDescription(
        title="Количество лайков",
        type="integer",
    ),
    "likes": FieldDescription(
        title="Количество дизлайков",
        type="integer",
    ),
    "imports": FieldDescription(
        title="Количество упоминаний",
        type="integer",
    ),
    "Type": FieldDescription(
        title="Тип записи",
        type="string",
    ),
}
