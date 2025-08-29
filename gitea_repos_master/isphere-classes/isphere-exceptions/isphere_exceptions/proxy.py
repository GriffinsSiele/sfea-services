from typing import Optional

from isphere_exceptions import (
    BlockedException,
    CommonException,
    ConfigurationInvalidException,
    ConnectionException,
    ErrorReturnToQueue,
    ISphereException,
    LockedException,
    OperationFailureException,
    ParseErrorException,
    TimeoutException,
)


class ProxyErrorInterface(ISphereException):
    """Интерфейс ошибки для работы с прокси

    Ошибка с кодом 521, префикс PRX.
    """

    EXCEPTION_PREFIX = "PRX"
    TEMPLATE_CONTEXT: Optional[str] = "proxy"

    def __init__(self, *args, **kwargs):
        super().__init__(*args, code=521, **kwargs)


class ProxyError(ProxyErrorInterface, CommonException):
    pass


class ProxyServerConfigurationInvalid(ProxyErrorInterface, ConfigurationInvalidException):
    TEMPLATE_CONTEXT = "сервису proxy"


class ProxyServerConnection(ProxyErrorInterface, ConnectionException):
    TEMPLATE_CONTEXT = "сервису proxy"


class ProxyServerTimeout(ProxyErrorInterface, TimeoutException):
    TEMPLATE_CONTEXT = "сервису proxy"


class ProxyServerOperationFailure(ProxyErrorInterface, OperationFailureException):
    TEMPLATE_CONTEXT = "сервиса proxy"


class ProxyServerParseError(ProxyErrorInterface, ParseErrorException):
    pass


class ProxyLocked(ProxyErrorInterface, LockedException):
    pass


class ProxyBlocked(ProxyErrorInterface, BlockedException):
    pass


class ProxyTimeout(ProxyErrorInterface, ErrorReturnToQueue):
    DEFAULT_MESSAGE = "Превышен таймаут ожидания ответа от {}. Возможна ротация IP"

    def __init__(self, *args, internal_code=508, **kwargs):
        super().__init__(*args, internal_code=internal_code, **kwargs)


class ProxyAuthenticationRequired(ProxyErrorInterface, ErrorReturnToQueue):
    DEFAULT_MESSAGE = "Ошибка в авторизации {}. Проверьте логин и пароль"

    def __init__(self, *args, internal_code=509, **kwargs):
        super().__init__(*args, internal_code=internal_code, **kwargs)


class ProxyUnavailable(ProxyErrorInterface, ErrorReturnToQueue):
    DEFAULT_MESSAGE = "Недоступен {}"

    def __init__(self, *args, internal_code=510, **kwargs):
        super().__init__(*args, internal_code=internal_code, **kwargs)


class ProxyTemporaryUnavailable(ProxyErrorInterface, ErrorReturnToQueue):
    DEFAULT_MESSAGE = "Временно недоступен {}"

    def __init__(self, *args, internal_code=511, **kwargs):
        super().__init__(*args, internal_code=internal_code, **kwargs)
