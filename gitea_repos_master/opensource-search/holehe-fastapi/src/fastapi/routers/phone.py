from typing import Any

from fastapi import APIRouter, Body
from fastapi import status as code
from src.fastapi.routers.interface import _search_process
from src.fastapi.schemas import ModuleResponse, SearchPhonePayload
from src.logic.holehe_search.modules import MODULES
from src.logic.ignorant_search.modules import ACTIVE_MODULES
from src.logic.ignorant_search.search import SearchIgnorantManager

phone_router = APIRouter(prefix="/phone", tags=["phone"])


@phone_router.get(
    "/modules/active",
    response_model=ModuleResponse,
    summary="Список активных модулей для поиска по телефону",
)
async def active_modules() -> dict:
    return {"modules": ACTIVE_MODULES}


@phone_router.get(
    "/modules/all",
    response_model=ModuleResponse,
    summary="Список всех модулей для поиска по телефону",
)
async def all_modules() -> dict:
    return {"modules": MODULES}


@phone_router.post(
    "/search",
    status_code=code.HTTP_200_OK,
    # responses=SearchResponse.model_config["search_responses_examples"],
)
async def search(
    search_data: SearchPhonePayload = Body(
        openapi_examples=SearchPhonePayload.model_config["openapi_examples"]
    ),
    only_found: bool = False,
) -> Any:
    return await _search_process(SearchIgnorantManager, search_data, only_found)
