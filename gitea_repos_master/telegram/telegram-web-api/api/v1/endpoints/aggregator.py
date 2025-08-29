from typing import List

from fastapi import APIRouter, Depends

from src.application.dto.aggregate_request import AggregateRequest
from src.application.dto.aggregate_response import AggregateResponse, ItemResult
from src.interface.controllers.telegram_controller import (
    TelegramController,
    get_telegram_controller,
)
from src.application.dto.search_request import SearchRequest
from src.infrastructure.validator.client import ValidatorClient


router = APIRouter(prefix="/api/v1", tags=["aggregator"])


@router.post("/aggregate", response_model=AggregateResponse)
async def aggregate(
    payload: AggregateRequest,
    telegram: TelegramController = Depends(get_telegram_controller),
) -> AggregateResponse:
    validator = ValidatorClient()
    results: List[ItemResult] = []

    for raw in payload.inputs:
        dtype = await validator.detect(raw)
        if dtype == "phone":
            response = await telegram.search_user(SearchRequest(phone=raw))
            success = getattr(response, "success", False)
            data = getattr(response, "data", None)
            errors = getattr(response, "errors", None) or getattr(response, "details", None)
            results.append(ItemResult(input=raw, type=dtype, success=success, data=data, errors=errors))
        elif dtype == "username":
            response = await telegram.search_user(SearchRequest(username=raw))
            success = getattr(response, "success", False)
            data = getattr(response, "data", None)
            errors = getattr(response, "errors", None) or getattr(response, "details", None)
            results.append(ItemResult(input=raw, type=dtype, success=success, data=data, errors=errors))
        else:
            results.append(ItemResult(input=raw, type=dtype, success=False, data=None, errors=["Unsupported input type"]))

    overall_success = any(r.success for r in results)
    return AggregateResponse(success=overall_success, results=results, errors=None, metadata=None)



