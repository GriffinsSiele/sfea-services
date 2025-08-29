from worker_classes.keydb.interfaces import FieldDescription, FieldXMLDescriptor

field_XML_description: FieldXMLDescriptor = {
    "address": FieldDescription(
        title="Адрес",
        type="string",
    ),
    "address_comment": FieldDescription(
        title="Примечание к адресу",
        type="string",
    ),
    "has_verified_owner": FieldDescription(
        title="Аккаунт владельца привязан к Яндекс.Бизнес",
        type="string",
    ),
    "categories": FieldDescription(
        title="Категории",
        type="string",
    ),
    "coordinates": FieldDescription(
        title="Местоположение",
        type="map",
    ),
    "country": FieldDescription(
        title="Страна",
        type="string",
    ),
    "description": FieldDescription(
        title="Описание объекта",
        description="Описание",
        type="text",
    ),
    "full_address": FieldDescription(
        title="Полный адрес",
        type="string",
    ),
    "panorama": FieldDescription(
        title="Панорамный вид с Яндекс.Карт",
        type="image",
    ),
    "images": FieldDescription(
        title="Изображения",
        type="image",
    ),
    "count_images": FieldDescription(
        title="Кол-во изображений",
        type="integer",
    ),
    "post_code": FieldDescription(
        title="Индекс",
        type="string",
    ),
    "seoname": FieldDescription(title="Теги SEO", type="string"),
    "rating_general": FieldDescription(
        title="Рейтинг",
        type="float",
    ),
    "rating_count": FieldDescription(
        title="Кол-во оценок рейтинга",
        type="integer",
    ),
    "review_count": FieldDescription(
        title="Кол-во отзывов",
        type="integer",
    ),
    "city": FieldDescription(
        title="Город",
        type="string",
    ),
    "name": FieldDescription(
        title="Название",
        type="string",
    ),
    "type": FieldDescription(
        title="Тип организации",
        type="string",
    ),
    "schedule": FieldDescription(
        title="Расписание работы",
        type="string",
    ),
    "url_yandex": FieldDescription(
        title="Ссылка в Яндекс.Картах",
        type="url",
    ),
    "abilities": FieldDescription(
        title="Доп. информация",
        type="text",
    ),
    "phone": FieldDescription(
        title="Телефон",
        type="phone",
    ),
    "url": FieldDescription(
        title="Ссылка на организацию",
        type="url",
    ),
    "vkontakte": FieldDescription(
        title="Страница VK",
        type="url",
    ),
    "instagram": FieldDescription(
        title="Страница Instagram",
        type="url",
    ),
    "facebook": FieldDescription(
        title="Страница Facebook",
        type="url",
    ),
    "twitter": FieldDescription(
        title="Страница Twitter",
        type="url",
    ),
    "youtube": FieldDescription(
        title="Страница Youtube",
        type="url",
    ),
    "whatsapp": FieldDescription(
        title="Whatsapp",
        type="url",
    ),
    "viber": FieldDescription(
        title="Viber",
        type="url",
    ),
    "telegram": FieldDescription(
        title="Telegram",
        type="url",
    ),
    "other_url": FieldDescription(
        title="Иные ссылки",
        type="url",
    ),
    "yandex_zen": FieldDescription(
        title="Яндекс Дзен",
        type="url",
    ),
    "ok": FieldDescription(
        title="Одноклассники",
        type="url",
    ),
    "business_images": FieldDescription(
        title="Изображение из Бизнес профиля",
        type="image",
    ),
    "load": FieldDescription(
        title="Посещаемость",
        type="text",
    ),
    "has_stories": FieldDescription(
        title="Есть истории",
        type="string",
    ),
    "neuro_review": FieldDescription(
        title="Отзыв нейросети",
        type="text",
    ),
    "stops": FieldDescription(
        title="Ближайшие остановки",
        type="text",
    ),
    "metro": FieldDescription(
        title="Ближайшее метро",
        type="text",
    ),
    "short_title": FieldDescription(
        title="Краткое название",
        type="string",
    ),
    "has_commercial": FieldDescription(
        title="Есть реклама в Яндекс.Картах",
        type="string",
    ),
    "commercial_article": FieldDescription(
        title="Рекламный заголовок",
        type="text",
    ),
    "commercial_text": FieldDescription(
        title="Текст рекламы",
        type="text",
    ),
    "commercial_logo": FieldDescription(
        title="Рекламный логотип",
        type="image",
    ),
    "commercial_url": FieldDescription(
        title="Рекламная ссылка",
        type="url",
    ),
    "commercial_banner": FieldDescription(
        title="Рекламный баннер",
        type="image",
    ),
    "aspects": FieldDescription(
        title="Рекомендации пользователей",
        type="text",
    ),
}
