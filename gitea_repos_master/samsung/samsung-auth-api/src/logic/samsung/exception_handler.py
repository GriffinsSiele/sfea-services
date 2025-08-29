"""
Модуль содержит код обработки исключений и ответа без исключений.
"""

import functools
from collections import OrderedDict
from typing import Any, Callable

from isphere_exceptions.success import NoDataEvent
from isphere_exceptions.worker import InternalWorkerError, InternalWorkerTimeout
from mongo_client.client import MongoSessions
from pydantic import ValidationError
from pydash import omit
from worker_classes.keydb.response_builder import KeyDBResponseBuilder
from worker_classes.thread.exception_handler import ExceptionHandler
from worker_classes.utils import short

from src.exceptions.session import SessionBlockedInfo
from src.fastapi.schemas import (
    SamsungDataRecords,
    SamsungDataRecordsWithEmail,
    SamsungSearchResponse,
    SamsungSearchResponseWithEmails,
)
from src.fastapi.server import handler_name_contextvar
from src.logger.context_logger import logging
from src.utils import ExtStr


async def normal(
    search_result: dict, session_storage: MongoSessions, session: dict, *args, **kwargs
) -> SamsungSearchResponse | SamsungSearchResponseWithEmails:
    """Обработка ответа без исключений.

    :param search_result: Результат поиска.
    :param session_storage: Подключение к бозе данных, которая хранит сессии.
    :param session: Использованная при обработке запроса сессия.
    :param args: Необязательные позиционные аргументы.
    :param kwargs: Необязательные ключевые аргументы.
    :return: Результат поиска в формате SamsungSearchResponse.
    """
    await _session_success_increment(session_storage, session)
    handler_name = handler_name_contextvar.get()
    try:
        if handler_name == "auth" or handler_name == "name":
            records = SamsungDataRecords(**search_result).dict()
            return SamsungSearchResponse(**KeyDBResponseBuilder.ok([records]))
        if handler_name == "person":
            records = SamsungDataRecordsWithEmail(**search_result).dict()
            return SamsungSearchResponseWithEmails(**KeyDBResponseBuilder.ok([records]))
        raise ValidationError
    except ValidationError as e:
        logging.error(f"ValidationError: {short(e)}")
        return SamsungSearchResponse(
            **KeyDBResponseBuilder.error(InternalWorkerError(), 500)
        )


async def not_found(
    exception: Exception, session_storage: MongoSessions, session: dict, *args, **kwargs
) -> SamsungSearchResponse:
    """Обработка исключения Пользователь не найден (NoDataEvent).

    :param exception: Исключение.
    :param session_storage: Подключение к бозе данных, которая хранит сессии.
    :param session: Использованная при обработке запроса сессия.
    :param args: Необязательные позиционные аргументы.
    :param kwargs: Необязательные ключевые аргументы.
    :return: Результат поиска в формате SamsungSearchResponse.
    """
    await _session_success_increment(session_storage, session)
    return SamsungSearchResponse(**KeyDBResponseBuilder.error(exception))


async def block_session(
    exception: Exception, session_storage: MongoSessions, session: dict, *args, **kwargs
) -> SamsungSearchResponse:
    """Обработка исключения и блокировка использованной сессии.

    :param exception: Исключение.
    :param session_storage: Подключение к бозе данных, которая хранит сессии.
    :param session: Использованная при обработке запроса сессия.
    :param args: Необязательные позиционные аргументы.
    :param kwargs: Необязательные ключевые аргументы.
    :return: Результат поиска в формате SamsungSearchResponse.
    """
    logging.info(f"Session blocked: {omit(session, 'session')}")
    await session_storage.session_inactive(session)
    return SamsungSearchResponse(**KeyDBResponseBuilder.error(exception))


async def base_exception(exception: Exception, *args, **kwargs) -> SamsungSearchResponse:
    """Обработка исключения

    :param exception: Исключение.
    :param args: Необязательные позиционные аргументы.
    :param kwargs: Необязательные ключевые аргументы.
    :return: Результат поиска в формате SamsungSearchResponse.
    """
    return SamsungSearchResponse(**KeyDBResponseBuilder.error(exception))


async def _session_success_increment(
    session_storage: MongoSessions, session: dict
) -> None:
    """Увеличивает счетчик успешного использования сессии.

    :param session_storage: Подключение к бозе данных, которая хранит сессии.
    :param session: Использованная при обработке запроса сессия.
    :return: None.
    """
    try:
        await session_storage.session_success(session, 1)
    except Exception as e:
        logging.warning(f"Session success increment failed: {ExtStr(e).inline()}")


order_exceptions = OrderedDict(
    [
        (None, normal),
        (NoDataEvent, not_found),
        (SessionBlockedInfo, block_session),
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
                session_storage=None,
                session=None,
                *args,
                **kwargs,
            )

    return wrapper
