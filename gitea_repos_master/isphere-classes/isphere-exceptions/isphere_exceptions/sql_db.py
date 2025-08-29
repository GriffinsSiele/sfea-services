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


class SQLDBErrorInterface(ISphereException):
    """Интерфейс ошибки для работы с базами SQL

    Ошибка с кодом 527, префикс SQL
    """

    EXCEPTION_PREFIX = "SQL"
    TEMPLATE_CONTEXT: Optional[str] = "SQL DB"

    def __init__(self, *args, **kwargs):
        super().__init__(*args, code=527, **kwargs)


class SQLDBError(SQLDBErrorInterface, CommonException):
    pass


class SQLDBConfigurationInvalid(SQLDBErrorInterface, ConfigurationInvalidException):
    pass


class SQLDBConnection(SQLDBErrorInterface, ConnectionException):
    pass


class SQLDBTimeout(SQLDBErrorInterface, TimeoutException):
    pass


class SQLDBOperationFailure(SQLDBErrorInterface, OperationFailureException):
    pass


class SQLDBParseError(SQLDBErrorInterface, ParseErrorException):
    pass
