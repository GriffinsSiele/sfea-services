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


class MongoErrorInterface(ISphereException):
    """Интерфейс ошибки для работы с MongoDB

    Ошибка с кодом 522, префикс MNG.
    """

    EXCEPTION_PREFIX = "MNG"
    TEMPLATE_CONTEXT: Optional[str] = "MongoDB"

    def __init__(self, *args, **kwargs):
        super().__init__(*args, code=522, **kwargs)


class MongoError(MongoErrorInterface, CommonException):
    pass


class MongoConfigurationInvalid(MongoErrorInterface, ConfigurationInvalidException):
    pass


class MongoConnection(MongoErrorInterface, ConnectionException):
    pass


class MongoTimeout(MongoErrorInterface, TimeoutException):
    pass


class MongoOperationFailure(MongoErrorInterface, OperationFailureException):
    pass


class MongoParseError(MongoErrorInterface, ParseErrorException):
    pass
