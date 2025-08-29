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


class KeyDBErrorInterface(ISphereException):
    """Интерфейс ошибки для работы с KeyDB

    Ошибка с кодом 520, префикс KDB.
    """

    EXCEPTION_PREFIX = "KDB"
    TEMPLATE_CONTEXT: Optional[str] = "KeyDB"

    def __init__(self, *args, **kwargs):
        super().__init__(*args, code=520, **kwargs)


class KeyDBError(KeyDBErrorInterface, CommonException):
    pass


class KeyDBConfigurationInvalid(KeyDBErrorInterface, ConfigurationInvalidException):
    pass


class KeyDBConnection(KeyDBErrorInterface, ConnectionException):
    pass


class KeyDBTimeout(KeyDBErrorInterface, TimeoutException):
    pass


class KeyDBOperationFailure(KeyDBErrorInterface, OperationFailureException):
    pass


class KeyDBParseError(KeyDBErrorInterface, ParseErrorException):
    pass
