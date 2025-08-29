from worker_classes.keydb.interfaces import FieldDescription, FieldXMLDescriptor

field_XML_description: FieldXMLDescriptor = {
    "id": FieldDescription(
        title="id пользователя",
        type="integer",
    ),
    "username": FieldDescription(
        title="Никнейм",
        type="nick",
    ),
    "link": FieldDescription(
        title="Ссылка на профиль",
        type="url",
    ),
    "first_name": FieldDescription(
        title="Имя",
        type="string",
    ),
    "last_name": FieldDescription(
        title="Фамилия",
        type="string",
    ),
    "has_photo": FieldDescription(
        title="Установлен аватар",
        type="string",
    ),
    "status": FieldDescription(
        title="Был в сети",
        type="string",
    ),
    "emoji_status": FieldDescription(
        title="Установлен Emoji статус",
        type="string",
    ),
    "usernames": FieldDescription(
        title="Другие никнеймы",
        type="string",
    ),
    "premium": FieldDescription(
        title="Premium профиль",
        type="string",
    ),
    "bot": FieldDescription(
        title="Бот",
        type="string",
    ),
    "verified": FieldDescription(
        title="Верифицированный профиль",
        type="string",
    ),
    "restricted": FieldDescription(
        title="Ограниченный профиль",
        type="string",
    ),
    "support": FieldDescription(
        title="Поддержка",
        type="string",
    ),
    "scam": FieldDescription(
        title="Наличие мошенничества",
        type="string",
    ),
    "fake": FieldDescription(
        title="Фальшивый профиль",
        type="string",
    ),
    "description": FieldDescription(
        title="О себе",
        type="text",
    ),
    "phone_calls_available": FieldDescription(
        title="Возможность звонка по телефону профилю",
        type="string",
    ),
    "video_calls_available": FieldDescription(
        title="Возможность видеозвонка профилю",
        type="string",
    ),
    "voice_messages_forbidden": FieldDescription(
        title="Запрещены входные голосовые сообщения",
        type="string",
    ),
    "image": FieldDescription(
        title="Аватар",
        type="image",
    ),
}
