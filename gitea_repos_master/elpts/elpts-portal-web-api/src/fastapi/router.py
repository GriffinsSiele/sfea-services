import warnings
from typing import Type

from pydantic import ValidationError
from worker_classes.logic.search_manager import SearchManager
from worker_classes.thread.timing import timing

from fastapi import APIRouter, Body, Response
from fastapi import status as code
from src.fastapi.schemas import (
    ElPtsSearchDataEpts,
    ElPtsSearchDataVin,
    ElPtsSearchResponse,
    ElPtsStatusResponse,
)
from src.logger.context_logger import logging
from src.logger.logger_adapter import request_id_contextvar
from src.logic.adapters import ResponseAdapter
from src.logic.elpts.exception_handler import exception_wrapper
from src.logic.elpts.search_manager_epts import ElPtsSearchManagerEpts
from src.logic.elpts.search_manager_vin import ElPtsSearchManagerVin

warnings.filterwarnings("ignore")

elpts_router = APIRouter(tags=["elpts"])


@elpts_router.get("/status", response_model=ElPtsStatusResponse)
async def status() -> dict:
    """Проверка состояния сервиса."""
    return {"status": "ok"}


@elpts_router.post(
    "/search/vin",
    status_code=code.HTTP_200_OK,
    responses=ElPtsSearchResponse.Config.search_responses_examples,
)
async def search_by_vin(
    response: Response,
    search_data: ElPtsSearchDataVin = Body(
        openapi_examples=ElPtsSearchDataVin.Config.openapi_examples
    ),
) -> ElPtsSearchResponse | None:
    """Поиск автомобиля по VIN номеру или заводскому номеру.

    - **VIN**: вин номер автомобиля
    - **BodyNum**: заводской номер автомобиля
    """
    return await timing("Total processing task time")(_search)(
        ElPtsSearchManagerVin, response, search_data
    )


@elpts_router.post(
    "/search/epts",
    status_code=code.HTTP_200_OK,
    responses=ElPtsSearchResponse.Config.search_responses_examples,
)
async def search_by_epts(
    response: Response,
    search_data: ElPtsSearchDataEpts = Body(
        openapi_examples=ElPtsSearchDataEpts.Config.openapi_examples
    ),
) -> ElPtsSearchResponse | None:
    """Поиск автомобиля по электронному паспорту транспортного средства (ЭПТС).

    - **EPTS**: электронный паспорт транспортного средства, допускаются только арабские цифры, 15 знаков
    """
    return await timing("Total processing task time")(_search)(
        ElPtsSearchManagerEpts, response, search_data
    )


async def _search(
    search_manager: Type[SearchManager],
    response: Response,
    search_data: ElPtsSearchDataVin = Body(
        openapi_examples=ElPtsSearchDataVin.Config.openapi_examples
    ),
) -> ElPtsSearchResponse | None:
    """
    Поиск транспортного средства в соответствии с переданной стратегией (SearchManager).
    """
    if search_data.payload:
        request_id_contextvar.set(search_data.payload)
    logging.info(f"LPOP {search_data.payload}")

    manager = search_manager()
    search_result: ElPtsSearchResponse = await _prepare_search_manager(manager)
    if not search_result:
        search_result = await manager.search(search_data.payload)

    if not search_result:
        logging.error("Search result is empty")
        search_result = ResponseAdapter.error(code=599, message="Неизвестная ошибка")

    if search_result.code == code.HTTP_204_NO_CONTENT:
        response.status_code = code.HTTP_204_NO_CONTENT
        logging.info(f"KeyDB response: [{search_result.code}] ok")
        return None

    response.status_code = search_result.code
    try:
        ElPtsSearchResponse.model_validate(search_result.model_dump(), strict=True)
        logging.info(f"KeyDB response: [{search_result.code}] {search_result.message}")
    except ValidationError as e:
        logging.warning(str(e).replace("\n", " "))
        logging.error("Response ValidationError")
    return search_result


@exception_wrapper
async def _prepare_search_manager(manager: SearchManager) -> ElPtsSearchResponse | None:
    return await manager.prepare()
