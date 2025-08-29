import functools
from collections import OrderedDict
from typing import Any, Callable

from isphere_exceptions.worker import InternalWorkerError
from pydantic import ValidationError
from worker_classes.keydb.response_builder import KeyDBResponseBuilder
from worker_classes.thread.exception_handler import ExceptionHandler
from worker_classes.utils import short

from src.fastapi.schemas import XiaomiDataRecords, XiaomiSearchResponse
from src.logger.context_logger import logging


async def normal(search_result: dict, *args, **kwargs) -> XiaomiSearchResponse:
    """Обработка ответа без исключений.

    :param search_result: Результат поиска.
    :param args: Необязательные позиционные аргументы.
    :param kwargs: Необязательные ключевые аргументы.
    :return: Результат поиска в формате XiaomiSearchResponse.
    """
    try:
        records = XiaomiDataRecords(**search_result).dict()
        return XiaomiSearchResponse(**KeyDBResponseBuilder.ok([records]))
    except ValidationError as e:
        logging.error(f"ValidationError: {short(e)}")
        return XiaomiSearchResponse(
            **KeyDBResponseBuilder.error(InternalWorkerError(), 500)
        )


async def base_exception(exception: Exception, *args, **kwargs) -> XiaomiSearchResponse:
    """Обработка исключения

    :param exception: Исключение.
    :param args: Необязательные позиционные аргументы.
    :param kwargs: Необязательные ключевые аргументы.
    :return: Результат поиска в формате XiaomiSearchResponse.
    """
    return XiaomiSearchResponse(**KeyDBResponseBuilder.error(exception))


order_exceptions = OrderedDict(
    [
        (None, normal),
        (TimeoutError, base_exception),
        (Exception, base_exception),
    ]
)

exception_handler = ExceptionHandler(order_exceptions)


def exception_wrapper(func: Callable) -> Callable:
    @functools.wraps(func)
    async def wrapper(*args, **kwargs) -> Any:
        try:
            return await func(*args, **kwargs)
        except Exception as e:
            return await exception_handler.call(
                e,
                logger=logging,
                search_data=None,
                search_result=None,
            )

    return wrapper
