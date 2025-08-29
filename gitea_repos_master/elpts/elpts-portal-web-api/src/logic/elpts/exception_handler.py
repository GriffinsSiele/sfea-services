import functools
import logging
from collections import OrderedDict
from typing import Any, Callable

from isphere_exceptions.worker import InternalWorkerError
from pydantic import ValidationError
from worker_classes.keydb.response_builder import KeyDBResponseBuilder
from worker_classes.thread.exception_handler import ExceptionHandler

from src.fastapi.schemas import ElPtsSearchResponse
from src.logic.adapters import ResponseAdapter


async def normal(search_result: dict, *args, **kwargs) -> ElPtsSearchResponse:
    """Обработка ответа без исключений.

    :param search_result: Результат поиска.
    :param args: Необязательные позиционные аргументы.
    :param kwargs: Необязательные ключевые аргументы.
    :return: Результат поиска в формате ElPtsSearchResponse.
    """
    try:
        return ResponseAdapter.ok(search_result)
    except ValidationError as e:
        logging.warning(f"ValidationError: " + str(e).replace("\n", " "))
        logging.error("Exception handler ValidationError")
        return ElPtsSearchResponse(
            **KeyDBResponseBuilder.error(InternalWorkerError(), 500)
        )


async def base_exception(exception: Exception, *args, **kwargs) -> ElPtsSearchResponse:
    """Обработка исключения

    :param exception: Исключение.
    :param args: Необязательные позиционные аргументы.
    :param kwargs: Необязательные ключевые аргументы.
    :return: Результат поиска в формате ElPtsSearchResponse.
    """
    return ElPtsSearchResponse(**KeyDBResponseBuilder.error(exception))


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
            return await exception_handler.call(e, search_result=None)

    return wrapper
