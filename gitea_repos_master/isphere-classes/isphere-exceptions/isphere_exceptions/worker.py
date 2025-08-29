from typing import Optional

from isphere_exceptions import CommonException, ErrorNoReturnToQueue, ISphereException


class InternalWorkerErrorInterface(ISphereException):
    """Интерфейс ошибки обработчика

    Ошибка с кодом 500-599, префикс INT
    """

    EXCEPTION_PREFIX = "INT"
    TEMPLATE_CONTEXT: Optional[str] = "Обработчик"

    def __init__(self, *args, code=599, **kwargs):
        super().__init__(*args, code=code, **kwargs)


class InternalWorkerError(InternalWorkerErrorInterface, CommonException):
    DEFAULT_MESSAGE = "Внутренняя ошибка обработчика"


class InternalWorkerTimeout(InternalWorkerErrorInterface, ErrorNoReturnToQueue):
    DEFAULT_MESSAGE = "Превышен таймаут ответа обработчика"

    log_level = "error"
    livenessprobe = False

    def __init__(self, *args, internal_code=510, **kwargs):
        super().__init__(*args, internal_code=internal_code, code=510, **kwargs)


class InternalWorkerQueueFull(InternalWorkerErrorInterface, ErrorNoReturnToQueue):
    DEFAULT_MESSAGE = "Слишком много запросов в очереди источника"

    log_level = "error"
    livenessprobe = False

    def __init__(self, *args, internal_code=511, **kwargs):
        super().__init__(*args, internal_code=internal_code, code=509, **kwargs)


class InternalWorkerMaintenance(InternalWorkerErrorInterface, ErrorNoReturnToQueue):
    DEFAULT_MESSAGE = "Сервис временно недоступен. Возможны технические работы"

    log_level = "warning"
    livenessprobe = True

    def __init__(self, *args, internal_code=513, **kwargs):
        super().__init__(*args, internal_code=internal_code, code=513, **kwargs)


class InternalWorkerOverload(InternalWorkerErrorInterface, ErrorNoReturnToQueue):
    DEFAULT_MESSAGE = "Сервис перегружен запросами"

    log_level = "error"
    livenessprobe = False

    def __init__(self, *args, internal_code=514, **kwargs):
        super().__init__(*args, internal_code=internal_code, code=514, **kwargs)


class InternalWorkerNotPrepared(InternalWorkerErrorInterface, ErrorNoReturnToQueue):
    DEFAULT_MESSAGE = "Произошла ошибка подготовки обработчика"

    log_level = "warning"
    livenessprobe = False

    def __init__(self, *args, internal_code=515, **kwargs):
        super().__init__(*args, internal_code=internal_code, code=515, **kwargs)


class UnknownError(InternalWorkerErrorInterface, ErrorNoReturnToQueue):
    DEFAULT_MESSAGE = "Неизвестная ошибка"

    log_level = "error"
    livenessprobe = False

    def __init__(self, *args, internal_code=599, **kwargs):
        super().__init__(*args, internal_code=internal_code, code=599, **kwargs)
