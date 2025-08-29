from typing import Optional

from isphere_exceptions import (
    BlockedException,
    CommonException,
    ConfigurationInvalidException,
    ConnectionException,
    ErrorNoReturnToQueue,
    ErrorReturnToQueue,
    ISphereException,
    LockedException,
    OperationFailureException,
    ParseErrorException,
    TimeoutException,
)


class JA3ErrorInterface(ISphereException):
    """Интерфейс ошибки для работы с ja3 server (tls proxy)

    Ошибка с кодом 524, префикс JA3.
    """

    EXCEPTION_PREFIX = "JA3"
    TEMPLATE_CONTEXT: Optional[str] = "JA3"

    def __init__(self, *args, **kwargs):
        super().__init__(*args, code=524, **kwargs)


class JA3Error(JA3ErrorInterface, CommonException):
    pass


class JA3ServerConfigurationInvalid(JA3ErrorInterface, ConfigurationInvalidException):
    pass


class JA3ServerConnection(JA3ErrorInterface, ConnectionException):
    pass


class JA3ServerTimeout(JA3ErrorInterface, TimeoutException):
    pass


class JA3ServerOperationFailure(JA3ErrorInterface, OperationFailureException):
    pass


class JA3ServerParseError(JA3ErrorInterface, ParseErrorException):
    pass


class JA3Locked(JA3ErrorInterface, LockedException):
    pass


class JA3Blocked(JA3ErrorInterface, BlockedException):
    pass


class JA3VersionTLS(JA3ErrorInterface, ErrorNoReturnToQueue):
    DEFAULT_MESSAGE = "Данная версия TLS {} для источника не поддерживается"

    livenessprobe = False
    log_level = "error"

    def __init__(self, *args, internal_code=508, **kwargs):
        super().__init__(internal_code=internal_code, *args, **kwargs)


class JA3InvalidPayload(JA3ErrorInterface, ErrorReturnToQueue):
    DEFAULT_MESSAGE = "Неверное тело запроса при отправке запроса {}"

    def __init__(self, *args, internal_code=509, **kwargs):
        super().__init__(internal_code=internal_code, *args, **kwargs)


class JA3MismatchUserAgent(JA3ErrorInterface, ErrorReturnToQueue):
    DEFAULT_MESSAGE = "Данный {} не может использоваться в связке с данным User-Agent. Проверьте введенные данные"

    def __init__(self, *args, internal_code=510, **kwargs):
        super().__init__(internal_code=internal_code, *args, **kwargs)


class JA3HandshakeError(JA3ErrorInterface, ErrorReturnToQueue):
    DEFAULT_MESSAGE = "Ошибка подключения {}"

    def __init__(self, *args, internal_code=511, **kwargs):
        super().__init__(internal_code=internal_code, *args, **kwargs)
