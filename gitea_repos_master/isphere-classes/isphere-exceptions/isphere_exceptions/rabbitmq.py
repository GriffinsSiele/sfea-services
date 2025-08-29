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


class RabbitMQErrorInterface(ISphereException):
    """Интерфейс ошибки для работы с RabbitMQ

    Ошибка с кодом 523, префикс RAB
    """

    EXCEPTION_PREFIX = "RAB"
    TEMPLATE_CONTEXT: Optional[str] = "RabbitMQ"

    def __init__(self, *args, **kwargs):
        super().__init__(*args, code=523, **kwargs)


class RabbitMQError(RabbitMQErrorInterface, CommonException):
    pass


class RabbitMQConfigurationInvalid(RabbitMQErrorInterface, ConfigurationInvalidException):
    pass


class RabbitMQConnection(RabbitMQErrorInterface, ConnectionException):
    pass


class RabbitMQTimeout(RabbitMQErrorInterface, TimeoutException):
    pass


class RabbitMQOperationFailure(RabbitMQErrorInterface, OperationFailureException):
    pass


class RabbitMQParseError(RabbitMQErrorInterface, ParseErrorException):
    pass
