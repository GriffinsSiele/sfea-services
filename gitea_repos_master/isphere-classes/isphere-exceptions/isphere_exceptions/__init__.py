from typing import Optional, Union


class CodeValidator:
    """
    Дескриптор для валидации кода ошибки.

    Ожидаемый диапазон кодов - [100, 1000)
    """

    def __init__(self):
        self.code = 100

    def __get__(self, *args, **kwargs):
        return self.code

    def __set__(self, instance, value):
        if value < 100 or value > 1000:
            raise ValueError("Code must be between 100 and 1000.")
        self.code = value


class ISphereException(Exception):
    """Базовый класс ошибки"""

    #: Дефолтное сообщение об ошибке, в случае если не указан метод в конструкторе
    DEFAULT_MESSAGE = "Событие i-sphere"

    #: Дефолтный префикс для внутреннего кода ошибки
    EXCEPTION_PREFIX = "INT"

    #: Дополнительные параметры, которые можно указать в сообщении ошибки
    #: при наличии шаблонизатора {} в ``self.message``
    TEMPLATE_CONTEXT: Optional[str] = None

    #: Код ошибки. По умолчанию - 500. Значение, возвращаемое клиенту.
    code = CodeValidator()

    log_level = "error"
    livenessprobe = False

    def __init__(
        self,
        message: Optional[str] = None,
        code: int = 500,
        internal_code: Union[str, int, None] = None,
    ):
        #: Произвольный текст ошибки
        self.message = str(message) if message else self.DEFAULT_MESSAGE
        if self.TEMPLATE_CONTEXT and "{}" in self.message:
            self.message = self.message.format(self.TEMPLATE_CONTEXT)
        self.code = code
        #: Код внутренней ошибки. Не обязательно численно совпадает с ``code``.
        #: Имеет в начале префикс ``EXCEPTION_PREFIX``. Формат: ``prefix-internal_code``
        self.internal_code = (
            f"{self.EXCEPTION_PREFIX}-{internal_code if internal_code else code}"
        )

    def __str__(self):
        return f"{type(self).__name__}({self.internal_code}) - {self.message}"

    def to_response(self):
        return self.message


class FailureEvent(ISphereException):
    """Событие с отрицательным результатом.

    Например, timeout подключения к БД, источник не отвечает и т.п.
    """

    DEFAULT_MESSAGE = "Общая ошибка"
    EXCEPTION_PREFIX = "INT"
    TEMPLATE_CONTEXT: Optional[str] = None

    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)


class SuccessEvent(ISphereException):
    """Событие с положительным результатом.

    Например, найдены данные или пользователь не существует в системе
    """

    DEFAULT_MESSAGE = "Успешное событие"
    EXCEPTION_PREFIX = "SCS"
    TEMPLATE_CONTEXT: Optional[str] = None

    log_level = "info"
    livenessprobe = True

    def __init__(self, *args, code=200, **kwargs):
        super().__init__(*args, code=code, **kwargs)


class ErrorNoReturnToQueue(SuccessEvent):
    """Ошибка обработки данных при которой входные данные не нужно возвращать обратно в очередь

    Все ошибки вида SuccessEvent
    """


class ErrorReturnToQueue(FailureEvent):
    """Ошибка обработки данных при которой входные данные необходимо вернуть обратно в очередь

    Все ошибки вида FailureEvent
    """


class CommonException(ErrorReturnToQueue):
    """Интерфейс общей ошибки при использовании зависимости (Keydb, Rabbitmq, Источник)

    Код - 500.
    """

    DEFAULT_MESSAGE = "Ошибка использования {}"

    def __init__(self, *args, internal_code=500, **kwargs):
        super().__init__(*args, internal_code=internal_code, **kwargs)


class ConfigurationInvalidException(ErrorNoReturnToQueue):
    """Интерфейс ошибки конфигурирования зависимости (Keydb, Rabbitmq, Источник)

    Код - 501
    """

    DEFAULT_MESSAGE = "Заданная конфигурация подключения к {} некорректна. Проверьте введенные параметры и ENV"

    livenessprobe = False
    log_level = "error"

    def __init__(self, *args, internal_code=501, **kwargs):
        super().__init__(*args, internal_code=internal_code, **kwargs)


class ConnectionException(ErrorNoReturnToQueue):
    """Интерфейс ошибки подключения к зависимости (Keydb, Rabbitmq, Источник)

    Код - 502
    """

    livenessprobe = False
    log_level = "error"

    DEFAULT_MESSAGE = "Ошибка подключения к {}"

    def __init__(self, *args, internal_code=502, **kwargs):
        super().__init__(*args, internal_code=internal_code, **kwargs)


class TimeoutException(ErrorReturnToQueue):
    """Интерфейс ошибки таймаута подключения к зависимости (Keydb, Rabbitmq, Источник)

    Код - 503
    """

    DEFAULT_MESSAGE = "Превышен таймаут подключения к {}, возможно недоступен"

    def __init__(self, *args, internal_code=503, **kwargs):
        super().__init__(*args, internal_code=internal_code, **kwargs)


class OperationFailureException(ErrorReturnToQueue):
    """Интерфейс ошибки выполнения действия у зависимости (Keydb, Rabbitmq, Источник)

    Код - 504
    """

    DEFAULT_MESSAGE = "При выполнении операции {} возникла ошибка"

    def __init__(self, *args, internal_code=504, **kwargs):
        super().__init__(*args, internal_code=internal_code, **kwargs)


class ParseErrorException(ErrorReturnToQueue):
    """Интерфейс ошибки обработки данных от зависимости (Keydb, Rabbitmq, Источник)

    Код - 505
    """

    DEFAULT_MESSAGE = "Возникла ошибка во время обработки данных {}"

    def __init__(self, *args, internal_code=505, **kwargs):
        super().__init__(*args, internal_code=internal_code, **kwargs)


class LockedException(ErrorReturnToQueue):
    """Интерфейс ошибки временной блокировки от зависимости (Keydb, Rabbitmq, Источник)

    Код - 506
    """

    DEFAULT_MESSAGE = "Данная конфигурация {} временно заблокирована для источника"

    def __init__(self, *args, internal_code=506, **kwargs):
        super().__init__(*args, internal_code=internal_code, **kwargs)


class BlockedException(ErrorReturnToQueue):
    """Интерфейс ошибки постоянной блокировки от зависимости (Keydb, Rabbitmq, Источник)

    Код - 507
    """

    DEFAULT_MESSAGE = "Данная конфигурация {} навсегда заблокирована для источника"

    def __init__(self, *args, internal_code=507, **kwargs):
        super().__init__(*args, internal_code=internal_code, **kwargs)
