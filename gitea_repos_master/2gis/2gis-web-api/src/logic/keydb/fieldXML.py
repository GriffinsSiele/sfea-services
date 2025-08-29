from worker_classes.keydb.interfaces import FieldDescription, FieldXMLDescriptor

field_XML_description: FieldXMLDescriptor = {
    "updated_at": FieldDescription(
        title="Дата обновления в 2GIS",
        type="string",
    ),
    "schedule": FieldDescription(
        title="Расписание работы",
        type="string",
    ),
    "rubrics": FieldDescription(
        title="Рубрики",
        type="string",
    ),
    "region": FieldDescription(
        title="Субъект РФ",
        type="string",
    ),
    "rating_general": FieldDescription(
        title="Рейтинг",
        type="float",
    ),
    "rating_count": FieldDescription(
        title="Кол-во оценок рейтинга",
        type="float",
    ),
    "post_code": FieldDescription(
        title="Индекс",
        type="string",
    ),
    "org_name": FieldDescription(
        title="Название организации",
        type="string",
    ),
    "name_ex": FieldDescription(
        title="Бывшее название",
        type="string",
    ),
    "name": FieldDescription(
        title="Название",
        type="string",
    ),
    "images": FieldDescription(
        title="Ссылка на аватар",
        type="image",
    ),
    "has_commercial": FieldDescription(
        title="Есть реклама в 2GIS",
        type="string",
    ),
    "floors": FieldDescription(
        title="Кол-во этажей в здании",
        type="float",
    ),
    "coordinates": FieldDescription(
        title="Местоположение",
        type="map",
    ),
    "building_name": FieldDescription(
        title="Название здания",
        type="string",
    ),
    "text_commercial": FieldDescription(
        title="Текст рекламы",
        type="text",
    ),
    "article_commercial": FieldDescription(
        title="Рекламный заголовок",
        type="text",
    ),
    "address_comment": FieldDescription(
        title="Примечание к адресу",
        type="string",
    ),
    "address": FieldDescription(
        title="Адрес",
        type="string",
    ),
    "vkontakte": FieldDescription(
        title="Страница VK",
        type="url",
    ),
    "website": FieldDescription(
        title="Сайт организации",
        type="url",
    ),
    "fax": FieldDescription(
        title="Факс",
        type="string",
    ),
    "facebook": FieldDescription(
        title="Страница Facebook",
        type="url",
    ),
    "twitter": FieldDescription(
        title="Страница Twitter",
        type="url",
    ),
    "email": FieldDescription(
        title="Почта",
        type="email",
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
    "phone": FieldDescription(
        title="Телефон",
        type="phone",
    ),
    "abilities": FieldDescription(
        title="Доп. информация",
        type="text",
    ),
    "is_deleted": FieldDescription(
        title="Удаленный объект",
        type="string",
    ),
    "caption": FieldDescription(
        title="Название объекта",
        type="string",
    ),
    "url": FieldDescription(
        title="Ссылка",
        type="url",
    ),
}
