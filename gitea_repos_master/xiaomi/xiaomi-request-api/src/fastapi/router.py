from pydantic import ValidationError
from worker_classes.keydb.response_builder import KeyDBResponseBuilder
from worker_classes.logic.search_manager import SearchManager
from worker_classes.thread.timing import timing
from worker_classes.utils import short

from fastapi import APIRouter, Body, Response
from fastapi import status as code
from src.fastapi.schemas import (
    XiaomiSearchData,
    XiaomiSearchResponse,
    XiaomiStatusResponse,
)
from src.logger.context_logger import logging
from src.logger.logger_adapter import request_id_contextvar
from src.logic.xiaomi.exception_handler import exception_wrapper
from src.logic.xiaomi.search_manager import XiaomiSearchManager

xiaomi_router = APIRouter(tags=["xiaomi"])


@xiaomi_router.get("/status", response_model=XiaomiStatusResponse)
async def status() -> dict:
    """Проверка состояния сервиса."""
    return {"status": "ok"}


@xiaomi_router.post(
    "/search",
    status_code=code.HTTP_200_OK,
    responses=XiaomiSearchResponse.Config.search_responses_examples,
)
async def search_user(
    response: Response,
    search_data: XiaomiSearchData = Body(
        openapi_examples=XiaomiSearchData.Config.openapi_examples
    ),
) -> XiaomiSearchResponse | None:
    """Поиск информации о пользователе.

    :param response: Ответ.
    :param search_data: Входные данные для поиска (адрес электронной почты или телефон).
    :return: Ответ в формате XiaomiSearchResponse.
    """
    return await timing("Total processing task time")(_search_user)(response, search_data)


async def _search_user(
    response: Response,
    search_data: XiaomiSearchData = Body(
        openapi_examples=XiaomiSearchData.Config.openapi_examples
    ),
) -> XiaomiSearchResponse | None:
    """Формирует ответ с информацией о пользователе в формате XiaomiSearchResponse.
    Код вынесен в отдельную функцию с целью применения декоратора timing.

    :param response: Ответ.
    :param search_data: Входные данные для поиска (адрес электронной почты или телефон).
    :return: Ответ в формате XiaomiSearchResponse.
    """
    if search_data.payload:
        request_id_contextvar.set(search_data.payload)
    logging.info(f"User input: {search_data.dict()}")
    logging.info(f"LPOP {search_data.payload}")

    manager = XiaomiSearchManager(logger=logging)
    search_result: XiaomiSearchResponse = await _prepare_search_manager(manager)
    if not search_result:
        search_result = await manager.search(search_data)

    if not search_result:
        logging.error("Search result is empty")
        search_result = XiaomiSearchResponse(
            **KeyDBResponseBuilder.error("Неизвестная ошибка", 599)
        )

    if search_result.code == code.HTTP_204_NO_CONTENT:
        response.status_code = code.HTTP_204_NO_CONTENT
        logging.info(f"KeyDB response: [{search_result.code}] ok")
        return None

    response.status_code = search_result.code
    try:
        XiaomiSearchResponse.model_validate(search_result.model_dump(), strict=True)
        logging.info(f"KeyDB response: [{search_result.code}] {search_result.message}")
    except ValidationError as e:
        logging.warning(short(e))
        logging.error("ValidationError")
    return search_result


@exception_wrapper
async def _prepare_search_manager(manager: SearchManager) -> XiaomiSearchResponse | None:
    return await manager.prepare()
