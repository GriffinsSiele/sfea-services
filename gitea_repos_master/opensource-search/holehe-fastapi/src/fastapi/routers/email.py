from typing import Any

from fastapi import APIRouter, Body
from fastapi import status as code
from src.fastapi.routers.interface import _search_process
from src.fastapi.schemas import ModuleResponse, SearchEmailPayload
from src.logic.holehe_search.modules import ACTIVE_MODULES, MODULES
from src.logic.holehe_search.search import SearchHoleheManager

email_router = APIRouter(prefix="/email", tags=["email"])


@email_router.get(
    "/modules/active",
    response_model=ModuleResponse,
    summary="Список активных модулей для поиска по почте",
)
async def active_modules() -> dict:
    return {"modules": ACTIVE_MODULES}


@email_router.get(
    "/modules/all",
    response_model=ModuleResponse,
    summary="Список всех модулей для поиска по почте",
)
async def all_modules() -> dict:
    return {"modules": MODULES}


@email_router.post(
    "/search",
    status_code=code.HTTP_200_OK,
    # responses=SearchResponse.model_config["search_responses_examples"],
)
async def search(
    search_data: SearchEmailPayload = Body(
        openapi_examples=SearchEmailPayload.model_config["openapi_examples"]
    ),
    only_found: bool = False,
) -> Any:
    return await _search_process(SearchHoleheManager, search_data, only_found)
