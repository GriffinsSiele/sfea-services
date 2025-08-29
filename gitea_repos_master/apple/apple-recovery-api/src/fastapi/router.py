from pydantic import ValidationError
from worker_classes.thread.timing import timing
from worker_classes.utils import short

from fastapi import APIRouter, Body, Response
from fastapi import status as code
from src.fastapi.schemas import AppleSearchData, AppleSearchResponse, AppleStatusResponse
from src.logger.context_logger import logging
from src.logger.logger_adapter import request_id_contextvar
from src.logic.adapters import ResponseAdapter
from src.logic.apple import AppleSearchManager
from src.logic.apple.exception_handler import exception_wrapper

apple_router = APIRouter(tags=["apple"])


@apple_router.get("/status", response_model=AppleStatusResponse)
async def status() -> dict:
    """Проверка состояния сервиса."""
    return {"status": "ok"}


@apple_router.post(
    "/search",
    status_code=code.HTTP_200_OK,
    responses=AppleSearchResponse.Config.search_responses_examples,
)
async def search_user(
    response: Response,
    search_data: AppleSearchData = Body(
        openapi_examples=AppleSearchData.Config.openapi_examples
    ),
) -> AppleSearchResponse | None:
    """Поиск информации о пользователе.

    :param response: Ответ.
    :param search_data: Входные данные для поиска (адрес электронной почты или телефон).
    :return: Ответ в формате AppleSearchResponse.
    """
    return await timing("Total processing task time")(_search_user)(response, search_data)


async def _search_user(
    response: Response,
    search_data: AppleSearchData = Body(
        openapi_examples=AppleSearchData.Config.openapi_examples
    ),
) -> AppleSearchResponse | None:
    """Формирует ответ с информацией о пользователе в формате AppleSearchResponse.
    Код вынесен в отдельную функцию с целью применения декоратора timing.

    :param response: Ответ.
    :param search_data: Входные данные для поиска (адрес электронной почты или телефон).
    :return: Ответ в формате AppleSearchResponse.
    """
    if search_data.payload:
        request_id_contextvar.set(search_data.payload)
    logging.info(f"LPOP {search_data.payload}")
    asm = AppleSearchManager(logger=logging)

    search_result: AppleSearchResponse = await _prepare_search_manager(asm)
    if not search_result:
        search_result = await asm.search(search_data)

    if not search_result:
        logging.error("Search result is empty")
        search_result = ResponseAdapter.error(code=599, message="Неизвестная ошибка")

    if search_result.code == code.HTTP_204_NO_CONTENT:
        response.status_code = code.HTTP_204_NO_CONTENT
        logging.info(f"KeyDB response: [{search_result.code}] ok")
        return None

    response.status_code = search_result.code
    try:
        AppleSearchResponse.model_validate(search_result.model_dump(), strict=True)
        logging.info(f"KeyDB response: [{search_result.code}] {search_result.message}")
    except ValidationError as e:
        logging.warning(short(e))
        logging.error("ValidationError")
    return search_result


@exception_wrapper
async def _prepare_search_manager(
    manager: AppleSearchManager,
) -> AppleSearchResponse | None:
    return await manager.prepare()
