field_XML_description = {
    'image_city': {
        'field': 'city_name',
        'description': 'Город в котором сделан снимок',
        'title': 'Город',
        'type': 'string'
    },
    'image_date': {
        'field': 'date',
        'description': 'Дата когда сделан снимок',
        'title': 'Дата',
        'type': 'string'
    },
    'image_region': {
        'field': 'image_region',
        'description': 'Субъект РФ где сделан снимок',
        'title': 'Субъект РФ',
        'type': 'string'
    },
    'image_url': {
        'field': 'image_url',
        'description': 'Фотография ТС',
        'title': 'Фото',
        'type': 'image'
    },
    'group_category': {
        'field': 'group_category',
        'description': 'Тип ссылки',
        'title': 'Тип ссылки',
        'type': 'string'
    },
    'group_description': {
        'field': 'group_description',
        'description': 'Краткое содержание по ссылки',
        'title': 'Содержание',
        'type': 'string'
    },
    'group_url': {
        'field': 'group_url',
        'description': 'Ссылка',
        'title': 'Ссылка',
        'type': 'url'
    }
}


def buildXML(field, value):
    return {**field_XML_description[field], 'value': value}
