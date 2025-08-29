import functools
from collections import OrderedDict
from typing import Any, Callable

from isphere_exceptions.worker import InternalWorkerError, InternalWorkerTimeout
from pydantic import ValidationError
from worker_classes.keydb.response_builder import KeyDBResponseBuilder
from worker_classes.thread.exception_handler import ExceptionHandler
from worker_classes.utils import short

from src.fastapi.schemas import AppleSearchResponse
from src.logger.context_logger import logging
from src.logic.adapters import ResponseAdapter


async def normal(
    search_data: str, search_result: dict, *args, **kwargs
) -> AppleSearchResponse:
    """Обработка ответа без исключений.

    :param search_data: Ключ поиска.
    :param search_result: Результат поиска.
    :param args: Необязательные позиционные аргументы.
    :param kwargs: Необязательные ключевые аргументы.
    :return: Результат поиска в формате AppleSearchResponse.
    """
    try:
        return ResponseAdapter.ok(search_result)
    except ValidationError as e:
        logging.warning(short(e))
        logging.error(f"ValidationError")
        return AppleSearchResponse(
            **KeyDBResponseBuilder.error(InternalWorkerError(), 500)
        )


async def base_exception(exception: Exception, *args, **kwargs) -> AppleSearchResponse:
    """Обработка исключения

    :param exception: Исключение.
    :param args: Необязательные позиционные аргументы.
    :param kwargs: Необязательные ключевые аргументы.
    :return: Результат поиска в формате AppleSearchResponse.
    """
    return AppleSearchResponse(**KeyDBResponseBuilder.error(exception))


order_exceptions = OrderedDict(
    [
        (None, normal),
        (InternalWorkerTimeout, base_exception),
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
