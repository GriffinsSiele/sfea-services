from typing import Optional

from isphere_exceptions import (
    CommonException,
    ConfigurationInvalidException,
    ConnectionException,
    ISphereException,
    OperationFailureException,
    ParseErrorException,
    TimeoutException,
)


class AutoBrowserErrorInterface(ISphereException):
    """Интерфейс ошибки управляемого браузера

    Ошибка с кодом 525, префикс BRW.
    """

    EXCEPTION_PREFIX = "BRW"
    TEMPLATE_CONTEXT: Optional[str] = "automated browser"

    def __init__(self, *args, **kwargs):
        super().__init__(*args, code=525, **kwargs)


class AutoBrowserError(AutoBrowserErrorInterface, CommonException):
    pass


class AutoBrowserConfigurationInvalid(
    AutoBrowserErrorInterface, ConfigurationInvalidException
):
    pass


class AutoBrowserConnection(AutoBrowserErrorInterface, ConnectionException):
    pass


class AutoBrowserTimeout(AutoBrowserErrorInterface, TimeoutException):
    pass


class AutoBrowserOperationFailure(AutoBrowserErrorInterface, OperationFailureException):
    pass


class AutoBrowserParseError(AutoBrowserErrorInterface, ParseErrorException):
    pass
