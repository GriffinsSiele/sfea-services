from isphere_exceptions import ErrorNoReturnToQueue


class FoundEvent(ErrorNoReturnToQueue):
    """Событие найденных данных в источнике

    Код 200, префикс SCS
    """

    DEFAULT_MESSAGE = "Найден"

    def __init__(self, *args, internal_code=200, **kwargs):
        super().__init__(*args, internal_code=internal_code, **kwargs)


class CachedEvent(ErrorNoReturnToQueue):
    """Событие найденных данных в источнике, использован кеш ответа

    Код 201, префикс SCS
    """

    DEFAULT_MESSAGE = "Найден, использован кеш предыдущего ответа"

    def __init__(self, *args, internal_code=201, **kwargs):
        super().__init__(*args, internal_code=internal_code, **kwargs)


class NoDataEvent(ErrorNoReturnToQueue):
    """Событие не найденных данных в источнике

    Код 204, префикс SCS
    """

    DEFAULT_MESSAGE = "Не найден"

    def __init__(self, *args, internal_code=204, **kwargs):
        super().__init__(*args, code=204, internal_code=internal_code, **kwargs)


class PartialContentEvent(ErrorNoReturnToQueue):
    """Событие частично найденных данных в источнике

    Код 206, префикс SCS
    """

    DEFAULT_MESSAGE = "Частично найден"

    def __init__(self, *args, internal_code=206, **kwargs):
        super().__init__(*args, code=206, internal_code=internal_code, **kwargs)
