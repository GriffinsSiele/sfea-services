from typing import Optional

from isphere_exceptions import (
    CommonException,
    ErrorNoReturnToQueue,
    ErrorReturnToQueue,
    ISphereException,
)


class SourceErrorInterface(ISphereException):
    """Интерфейс ошибки для работы с источником (сайт, приложение)

    Ошибка с кодом 530, префикс SRC
    """

    EXCEPTION_PREFIX = "SRC"
    TEMPLATE_CONTEXT: Optional[str] = "источник"

    def __init__(self, *args, code=530, **kwargs):
        super().__init__(*args, code=code, **kwargs)


class SourceError(SourceErrorInterface, CommonException):
    DEFAULT_MESSAGE = "Ошибка со стороны источника"


class SourceParseError(SourceErrorInterface, ErrorNoReturnToQueue):
    DEFAULT_MESSAGE = "Неподдерживаемый ответ источника"

    livenessprobe = False
    log_level = "warning"

    def __init__(self, *args, internal_code=501, **kwargs):
        super().__init__(*args, internal_code=internal_code, code=501, **kwargs)


class SourceConnection(SourceErrorInterface, ErrorNoReturnToQueue):
    DEFAULT_MESSAGE = "Источник не отвечает на запросы"

    livenessprobe = True

    def __init__(self, *args, internal_code=502, **kwargs):
        super().__init__(*args, internal_code=internal_code, code=502, **kwargs)


class SourceDown(SourceErrorInterface, ErrorNoReturnToQueue):
    DEFAULT_MESSAGE = "Источник выключен на стороне. Возможно сайт/приложение недоступно"

    livenessprobe = True

    def __init__(self, *args, internal_code=503, **kwargs):
        super().__init__(*args, internal_code=internal_code, code=503, **kwargs)


class SourceTimeout(SourceErrorInterface, ErrorReturnToQueue):
    DEFAULT_MESSAGE = "Превышен таймаут запроса к источнику"

    def __init__(self, *args, internal_code=504, **kwargs):
        super().__init__(*args, internal_code=internal_code, code=504, **kwargs)


class SourceIncorrectDataDetected(SourceErrorInterface, ErrorNoReturnToQueue):
    DEFAULT_MESSAGE = "Источник не может выполнить запрос по указанным данным"

    log_level = "warning"
    livenessprobe = True

    def __init__(self, *args, internal_code=505, **kwargs):
        super().__init__(*args, internal_code=internal_code, code=505, **kwargs)


class SourceVagueData(SourceErrorInterface, ErrorNoReturnToQueue):
    DEFAULT_MESSAGE = "Найдено слишком много совпадений"

    log_level = "warning"
    livenessprobe = True

    def __init__(self, *args, internal_code=506, **kwargs):
        super().__init__(*args, internal_code=internal_code, code=506, **kwargs)


class SourceLimitError(SourceErrorInterface, ErrorNoReturnToQueue):
    DEFAULT_MESSAGE = "Превышен лимит использования источника"

    def __init__(self, *args, internal_code=507, **kwargs):
        super().__init__(*args, internal_code=internal_code, code=507, **kwargs)


class SourceConfigurationInvalid(SourceErrorInterface, ErrorNoReturnToQueue):
    DEFAULT_MESSAGE = "Заданная конфигурация использования источника некорректна. Проверьте введенные параметры и настройки"

    def __init__(self, *args, internal_code=509, **kwargs):
        super().__init__(*args, internal_code=internal_code, **kwargs)


class SourceOperationFailure(SourceErrorInterface, ErrorReturnToQueue):
    DEFAULT_MESSAGE = "При выполнении операции возникла ошибка"

    def __init__(self, *args, internal_code=509, **kwargs):
        super().__init__(*args, internal_code=internal_code, **kwargs)
